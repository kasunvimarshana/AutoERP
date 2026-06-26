<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Security;

use DateTimeInterface;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Core\Contracts\ClockInterface;

final readonly class TokenValueParser
{
    public function __construct(private ClockInterface $clock) {}

    public function tokenKey(string $plainToken): string
    {
        $parts = explode('.', trim($plainToken), 2);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            throw $this->invalidToken();
        }

        return $parts[0];
    }

    public function isExpired(mixed $expiresAt): bool
    {
        return $expiresAt === null || $this->clock->now()->getTimestamp() >= $this->timestamp($expiresAt);
    }

    public function timestamp(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        throw new AuthFailure(AuthErrorCode::TOKEN_INVALID, 'Authentication state is invalid.', 401);
    }

    public function atom(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }

    public function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function invalidToken(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::TOKEN_INVALID, 'The authentication token is invalid.', 401);
    }
}
