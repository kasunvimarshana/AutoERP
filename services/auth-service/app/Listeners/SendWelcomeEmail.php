<?php

namespace App\Listeners;

use App\Domain\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        // TODO: implement actual email template
        Log::info('Welcome email queued for user', [
            'user_id'   => $event->user->id,
            'email'     => $event->user->email,
            'tenant_id' => $event->tenantId,
        ]);
    }
}
