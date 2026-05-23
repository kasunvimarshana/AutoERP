<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;

class TenantDomainService
{
    /** @var array<int, string> */
    private const TENANT_STATUSES = ['active', 'suspended', 'pending', 'cancelled'];

    /** @var array<int, string> */
    private const BILLING_INTERVALS = ['month', 'year'];

    public function normalizeText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        if ($slug === '') {
            throw new InvalidArgumentException('Slug cannot be empty.');
        }

        return $slug;
    }

    public function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));

        if ($domain === '') {
            throw new InvalidArgumentException('Domain cannot be empty.');
        }

        return $domain;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function assertTenantStatus(string $status): void
    {
        if (! in_array($status, self::TENANT_STATUSES, true)) {
            throw new InvalidArgumentException('Tenant status must be active, suspended, pending, or cancelled.');
        }
    }

    public function assertBillingInterval(string $interval): void
    {
        if (! in_array($interval, self::BILLING_INTERVALS, true)) {
            throw new InvalidArgumentException('Billing interval must be month or year.');
        }
    }

    public function assertNonNegativePrice(string|int|float $price): void
    {
        if ((float) $price < 0) {
            throw new InvalidArgumentException('Plan price cannot be negative.');
        }
    }
}
