<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendUserCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int    $tries = 3;

    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        Log::info('Sending welcome notification', [
            'user_id'   => $user->id,
            'email'     => $user->email,
            'tenant_id' => $user->tenant_id,
        ]);

        // In a production system this would send a real welcome e-mail.
        // We guard gracefully so the listener never crashes the entire queue.
        try {
            // Mail::to($user->email)->send(new \App\Mail\WelcomeUserMail($user));
            Log::info('Welcome notification dispatched', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome notification', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
