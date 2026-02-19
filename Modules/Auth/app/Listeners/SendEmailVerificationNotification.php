<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Email Verification Notification Listener
 *
 * Listens for user registration events and sends email verification notification.
 */
class SendEmailVerificationNotification implements ShouldQueue
{
    /**
     * Create the event listener
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event
     */
    public function handle(Registered $event): void
    {
        if (config('auth.email_verification.enabled', true) && ! $event->user->hasVerifiedEmail()) {
            try {
                $event->user->sendEmailVerificationNotification();

                Log::channel('auth')->info('Email verification sent', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                ]);
            } catch (\Exception $e) {
                Log::channel('auth')->error('Failed to send email verification', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
