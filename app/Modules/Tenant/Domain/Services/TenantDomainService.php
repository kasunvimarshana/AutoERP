<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Services;

use InvalidArgumentException;
use Modules\Tenant\Domain\Constants\TenantStatus;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use Modules\Tenant\Domain\ValueObjects\TenantIsolationKey;

final class TenantDomainService implements TenantDomainServiceInterface
{
    public function normalizeCode(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Tenant code is required.');
        }

        return $normalized;
    }

    public function normalizeName(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('Tenant name is required.');
        }

        return $normalized;
    }

    public function normalizeSlug(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Slug is required.');
        }

        return $normalized;
    }

    public function normalizeKey(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('Key is required.');
        }

        return $normalized;
    }

    public function normalizeDomain(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Domain is required.');
        }

        return $normalized;
    }

    public function normalizeBillingInterval(?string $value): string
    {
        $candidate = strtolower(trim((string) $value));
        if ($candidate === '') {
            return 'month';
        }

        if (! in_array($candidate, ['month', 'year'], true)) {
            throw new InvalidArgumentException('Billing interval must be month or year.');
        }

        return $candidate;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeStatus(?string $status): string
    {
        $candidate = strtolower(trim((string) $status));
        if ($candidate === '') {
            return TenantStatus::ACTIVE;
        }

        if (! TenantStatus::isValid($candidate)) {
            throw new InvalidArgumentException(sprintf('Unsupported tenant status "%s".', $candidate));
        }

        return $candidate;
    }

    public function deriveActiveFlag(string $status): bool
    {
        return $status === TenantStatus::ACTIVE;
    }

    public function ensureIsolationKey(bool $isIsolated, ?string $isolationKey, string $fallback): ?string
    {
        if (! $isIsolated) {
            return null;
        }

        $candidate = trim((string) ($isolationKey ?? ''));
        if ($candidate === '') {
            $candidate = trim($fallback);
        }

        return (string) new TenantIsolationKey($candidate);
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
