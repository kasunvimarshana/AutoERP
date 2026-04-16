<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

/**
 * Contract for initiating the password reset flow.
 * Sends a password-reset link to the given email address.
 */
interface ForgotPasswordServiceInterface
{
    /**
     * Send a password reset link to the given email.
     * Returns true when the link was queued, false when the email was not found.
     */
    public function sendResetLink(string $email): bool;
}
