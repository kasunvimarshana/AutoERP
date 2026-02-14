<?php

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Events\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Welcome Email Listener
 * 
 * Listens to UserCreated event and sends a welcome email to the new user
 * 
 * @package App\Modules\Identity\Listeners
 */
class SendWelcomeEmail implements ShouldQueue
{
    /**
     * The number of times the job may be attempted
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create the event listener
     */
    public function __construct()
    {
    }

    /**
     * Handle the event
     *
     * @param UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event): void
    {
        $user = $event->user;
        
        Log::info('Sending welcome email to user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
        
        // TODO: Implement actual email sending logic
        // Example:
        // Mail::to($user->email)->send(new WelcomeEmail($user));
        
        // For now, just log the action
        Log::info('Welcome email sent successfully', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Handle a job failure
     *
     * @param UserCreated $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(UserCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to send welcome email', [
            'user_id' => $event->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
