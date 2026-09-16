<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AI\CreditScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeCreditScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $userId = null
    ) {}

    public function handle(CreditScoringService $service): void
    {
        $query = User::where('is_active', true);

        if ($this->userId) {
            $query->where('id', $this->userId);
        }

        $query->chunk(100, function ($users) use ($service) {
            foreach ($users as $user) {
                $service->computeScore($user);
            }
        });
    }
}