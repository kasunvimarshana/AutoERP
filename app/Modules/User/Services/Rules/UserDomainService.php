<?php

declare(strict_types=1);

namespace Modules\User\Services\Rules;

use InvalidArgumentException;
use Modules\User\Constants\UserStatus;
use Modules\User\Services\Contracts\UserDomainServiceInterface;
use Modules\User\ValueObjects\UserEmail;

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

    public function normalizeRequiredString(string $value, string $field, int $maxLength = 255): string
    {
        $candidate = trim($value);
        if ($candidate === '') {
            throw new InvalidArgumentException(sprintf('%s is required.', $field));
        }

        if (mb_strlen($candidate) > $maxLength) {
            throw new InvalidArgumentException(
                sprintf('%s may not be greater than %d characters.', $field, $maxLength),
            );
        }

        return $candidate;
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
