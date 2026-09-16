<?php

namespace App\Jobs;

use App\Services\AI\UserBehaviorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogUserBehaviorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $userId,
        public string $sessionId,
        public string $eventType,
        public array $context = []
    ) {}

    public function handle(UserBehaviorService $service): void
    {
        $user = $this->userId ? \App\Models\User::find($this->userId) : null;
        $service->logEvent($user, $this->sessionId, $this->eventType, $this->context);
    }
}