<?php

declare(strict_types=1);

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Email Verified Listener
 *
 * Listens for email verification events and logs them for audit purposes.
 */
class LogEmailVerified implements ShouldQueue
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
    public function handle(Verified $event): void
    {
        Log::channel('auth')->info('Email verified', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
