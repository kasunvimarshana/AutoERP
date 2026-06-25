<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Rules;

use InvalidArgumentException;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;

final class TenantDomainService implements TenantDomainServiceInterface
{
    public function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '' || preg_match('/^[A-Z0-9][A-Z0-9_-]{1,49}$/', $value) !== 1) {
            throw new InvalidArgumentException('Tenant code must contain 2-50 uppercase letters, numbers, dashes, or underscores.');
        }
        return $value;
    }

    public function normalizeName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Tenant name is required.');
        }
        return $value;
    }

    public function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || preg_match('/^[a-z0-9](?:[a-z0-9-]{0,98}[a-z0-9])?$/', $value) !== 1) {
            throw new InvalidArgumentException('Tenant slug must be a lowercase URL-safe value.');
        }
        return $value;
    }

    public function normalizeDomain(string $value): string
    {
        $value = strtolower(rtrim(trim($value), '.'));
        if (
            $value === ''
            || strlen($value) > 253
            || str_contains($value, '://')
            || str_contains($value, '/')
            || str_contains($value, ':')
            || filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            throw new InvalidArgumentException('A hostname without a protocol, port, or path is required.');
        }

        return $value;
    }

    public function normalizeBillingInterval(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = $value === '' ? 'month' : $value;
        if (! in_array($value, ['month', 'quarter', 'year'], true)) {
            throw new InvalidArgumentException('Billing interval must be month, quarter, or year.');
        }
        return $value;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);
        return $value === '' ? null : $value;
    }

    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
