<?php

namespace Modules\IAM\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Thrown when rate limit is exceeded
 */
class RateLimitExceededException extends DomainException
{
    protected int $statusCode = 429;

    public static function forLogin(int $attempts, int $windowMinutes): self
    {
        return new self(
            "Too many login attempts. Please try again after {$windowMinutes} minutes.",
            [
                'attempts' => $attempts,
                'window_minutes' => $windowMinutes,
            ]
        );
    }

    public static function forAction(string $action, int $limitPerMinute): self
    {
        return new self(
            "Rate limit exceeded for action: {$action}. Limit: {$limitPerMinute} per minute.",
            [
                'action' => $action,
                'limit' => $limitPerMinute,
            ]
        );
    }
}
