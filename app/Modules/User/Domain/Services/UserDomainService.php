<?php

declare(strict_types=1);

namespace Modules\User\Domain\Services;

use InvalidArgumentException;
use Modules\User\Domain\Constants\UserStatus;
use Modules\User\Domain\Contracts\UserDomainServiceInterface;
use Modules\User\Domain\ValueObjects\UserEmail;

final class UserDomainService implements UserDomainServiceInterface
{
    public function normalizeStatus(?string $status): string
    {
        $candidate = strtolower(trim((string) $status));
        if ($candidate === '') {
            return UserStatus::ACTIVE;
        }

        if (! in_array($candidate, UserStatus::values(), true)) {
            throw new InvalidArgumentException('Invalid user status.');
        }

        return $candidate;
    }

    public function normalizeEmail(string $email): string
    {
        return (new UserEmail($email))->normalized();
    }

    public function normalizeNullableString(?string $value): ?string
    {
        $candidate = trim((string) $value);

        return $candidate === '' ? null : $candidate;
    }

    public function normalizeMetadata(mixed $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        if (! is_array($metadata)) {
            throw new InvalidArgumentException('Metadata must be an array.');
        }

        return $metadata;
    }

    public function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
