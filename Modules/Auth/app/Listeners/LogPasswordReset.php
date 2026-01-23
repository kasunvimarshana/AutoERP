<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Password Reset Listener
 *
 * Listens for password reset events and logs them for audit purposes.
 */
class LogPasswordReset implements ShouldQueue
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
    public function handle(PasswordReset $event): void
    {
        Log::channel('auth')->info('Password reset completed', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
