<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 30;

    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        try {
            Mail::send(
                [],
                [],
                function (\Illuminate\Mail\Message $message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Welcome to ' . config('app.name'))
                            ->html($this->buildHtml($user));
                }
            );

            Log::info('Welcome email sent', ['user_id' => $user->id, 'email' => $user->email]);
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    private function buildHtml(\App\Models\User $user): string
    {
        $appName  = e(config('app.name', 'SaaS Platform'));
        $userName = e($user->name);
        $appUrl   = e(config('app.url', 'https://example.com'));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Welcome</title></head>
        <body style="font-family: Arial, sans-serif; color: #333; padding: 20px;">
            <h2>Welcome to {$appName}, {$userName}!</h2>
            <p>Your account has been created successfully.</p>
            <p>You can log in at: <a href="{$appUrl}">{$appUrl}</a></p>
            <p>If you did not register, please ignore this email.</p>
            <br>
            <p>The {$appName} Team</p>
        </body>
        </html>
        HTML;
    }
}
