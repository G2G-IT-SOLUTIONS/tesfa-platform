<?php

namespace App\Http\Controllers;

use App\Services\GDPRComplianceService;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    public function __construct(
        private GDPRComplianceService $gdpr
    ) {}

    /**
     * Show consent page
     */
    public function show()
    {
        if (auth()->check() && auth()->user()->hasConsent('terms_of_service') && auth()->user()->hasConsent('privacy_policy')) {
            return redirect()->route('dashboard');
        }

        return view('pages.legal.consent');
    }

    /**
     * Store consents
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'consents' => 'required|array',
            'consents.terms_of_service' => 'required|accepted',
            'consents.privacy_policy' => 'required|accepted',
            'consents.data_processing' => 'required|accepted',
            'consents.credit_check' => 'nullable|boolean',
            'consents.marketing_emails' => 'nullable|boolean',
            'consents.analytics' => 'nullable|boolean',
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('register')
                ->with('info', 'Please create an account first.');
        }

        foreach ($validated['consents'] as $type => $granted) {
            $this->gdpr->recordConsent($user, $type, (bool) $granted, '1.0');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Your privacy preferences have been saved.');
    }

    /**
     * Withdraw a specific consent
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'consent_type' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // Cannot withdraw required consents
        if (in_array($validated['consent_type'], ['terms_of_service', 'privacy_policy', 'data_processing'])) {
            return back()->with('error', 'This consent is required to use the platform. You may close your account instead.');
        }

        $this->gdpr->recordConsent($user, $validated['consent_type'], false, '1.0');

        return back()->with('success', 'Consent withdrawn.');
    }

    /**
     * Show data request form
     */
    public function showDataRequest()
    {
        $user = auth()->user();

        $requests = $user->dataSubjectRequests()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('pages.legal.data-request', compact('requests'));
    }

    /**
     * Submit data request
     */
    public function submitDataRequest(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:access,rectification,erasure,restriction,portability,objection',
            'description' => 'nullable|string|max:2000',
        ]);

        $user = auth()->user();

        // Rate limit: one request per type per 24 hours
        $existing = $user->dataSubjectRequests()
            ->where('request_type', $validated['request_type'])
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            return back()->with('error', 'You already submitted this request type in the last 24 hours.');
        }

        $dsr = $this->gdpr->createDSR($user, $validated['request_type'], $validated['description'] ?? null);

        // If access request, process immediately
        if ($validated['request_type'] === 'access') {
            try {
                $path = $this->gdpr->exportUserData($user);

                $dsr->update([
                    'status' => 'fulfilled',
                    'completed_at' => now(),
                    'response_summary' => 'Data exported successfully.',
                    'response_file_path' => $path,
                ]);

                return back()->with('success', 'Your data export is ready. Check your email for the download link.');
            } catch (\Exception $e) {
                \Log::error('Data export failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                return back()->with('error', 'Export failed. Our team will process your request manually.');
            }
        }

        return back()->with('success', "Request #{$dsr->request_number} submitted. We'll process it within 30 days.");
    }
}