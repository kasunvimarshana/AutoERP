<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Rules;

use InvalidArgumentException;
use Modules\Auth\Constants\AuthStatus;
use Modules\Auth\Services\Contracts\AuthDomainServiceInterface;

final class AuthDomainService implements AuthDomainServiceInterface
{
    public function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        if ($normalized < 1) {
            throw new InvalidArgumentException('Numeric identifiers must be greater than 0.');
        }

        return $normalized;
    }

    public function normalizeRequiredString(string $value, string $field): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException($field.' is required.');
        }

        return $normalized;
    }

    public function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeStatus(?string $status, string $default = 'active'): string
    {
        $normalized = strtolower(trim((string) ($status ?? $default)));

        if (! in_array($normalized, AuthStatus::values(), true)) {
            throw new InvalidArgumentException('Invalid auth status: '.$normalized);
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Metadata must be an associative array.');
        }

        return $value;
    }
}
