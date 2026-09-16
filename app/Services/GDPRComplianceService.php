<?php

namespace App\Services;

use App\Models\User;
use App\Models\DataSubjectRequest;
use App\Models\UserConsent;
use App\Models\DataProcessingLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GDPRComplianceService
{
    /**
     * Export all user data (GDPR Article 20)
     */
    public function exportUserData(User $user): string
    {
        $export = [
            'export_date' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'properties' => $user->properties()->get()->toArray(),
            'escrows' => $user->escrows()->get()->toArray(),
            'payments' => $user->payments()->get()->toArray(),
            'behavior_logs' => $user->behaviorLogs()->limit(1000)->get()->toArray(),
            'credit_score' => $user->creditScore?->toArray(),
            'consents' => $user->consents()->get()->toArray(),
        ];

        $filename = "data_export_{$user->id}_" . now()->format('Ymd_His') . '.json';
        $path = "exports/{$filename}";

        Storage::put($path, json_encode($export, JSON_PRETTY_PRINT));

        DataProcessingLog::create([
            'user_id' => $user->id,
            'processing_activity' => 'data_export',
            'legal_basis' => 'consent',
            'data_categories' => json_encode(['personal', 'contact', 'financial', 'behavioral']),
            'retention_period_days' => 30,
        ]);

        return Storage::url($path);
    }

    /**
     * Delete user data (GDPR Article 17)
     */
    public function deleteUserData(User $user): array
    {
        $results = ['deleted' => [], 'anonymized' => [], 'retained' => []];

        DB::transaction(function () use ($user, &$results) {
            // Delete personal data
            $user->behaviorLogs()->delete();
            $results['deleted'][] = 'behavior_logs';

            // Anonymize transactions (required for legal/financial records)
            $user->escrows()->update([
                'buyer_id' => null,
                'seller_id' => null,
                'anonymized' => true,
            ]);
            $results['anonymized'][] = 'escrows';

            // Delete account
            $user->delete();
            $results['deleted'][] = 'user_account';

            // Retained for legal compliance
            $results['retained'][] = 'tax_records (anonymized)';
            $results['retained'][] = 'fraud_alerts (anonymized)';
        });

        return $results;
    }

    /**
     * Record consent (GDPR Article 7)
     */
    public function recordConsent(User $user, string $consentType, bool $granted, string $version): UserConsent
    {
        return UserConsent::updateOrCreate(
            ['user_id' => $user->id, 'consent_type' => $consentType],
            [
                'granted' => $granted,
                'granted_at' => $granted ? now() : null,
                'revoked_at' => $granted ? null : now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'consent_version' => $version,
            ]
        );
    }

    /**
     * Check if user has consented
     */
    public function hasConsent(User $user, string $consentType): bool
    {
        return UserConsent::where('user_id', $user->id)
            ->where('consent_type', $consentType)
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Run data retention cleanup
     */
    public function runRetentionCleanup(): array
    {
        $results = [];

        // Delete behavior logs older than 90 days
        $deleted = \App\Models\UserBehaviorLog::where('created_at', '<', now()->subDays(90))->delete();
        $results['behavior_logs_deleted'] = $deleted;

        // Delete expired chatbot conversations
        $deleted = \App\Models\ChatbotConversation::where('created_at', '<', now()->subDays(30))->delete();
        $results['chatbot_conversations_deleted'] = $deleted;

        // Anonymize old cancelled escrows
        $anonymized = \App\Models\Escrow::where('status', 'cancelled')
            ->where('cancelled_at', '<', now()->subYear())
            ->update(['buyer_id' => null, 'seller_id' => null]);

        $results['escrows_anonymized'] = $anonymized;

        return $results;
    }

    /**
     * Create data subject request (GDPR Articles 15-22)
     */
    public function createDSR(User $user, string $type, ?string $description = null): DataSubjectRequest
    {
        return DataSubjectRequest::create([
            'request_number' => $this->generateDSRNumber(),
            'user_id' => $user->id,
            'request_type' => $type,
            'request_description' => $description,
            'received_at' => now(),
            'due_at' => now()->addDays(30),
            'status' => 'received',
        ]);
    }

    private function generateDSRNumber(): string
    {
        $year = now()->year;
        $count = DataSubjectRequest::whereYear('created_at', $year)->count() + 1;
        return "DSR-{$year}-" . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}