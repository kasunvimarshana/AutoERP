<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Contracts;

interface TenantDomainServiceInterface
{
    public function normalizeCode(string $value): string;

    public function normalizeName(string $value): string;

    public function normalizeSlug(string $value): string;

    public function normalizeKey(string $value): string;

    public function normalizeDomain(string $value): string;

    public function normalizeBillingInterval(?string $value): string;

    public function normalizeOptionalText(?string $value): ?string;

    public function normalizeStatus(?string $status): string;

    public function deriveActiveFlag(string $status): bool;

    public function ensureIsolationKey(bool $isIsolated, ?string $isolationKey, string $fallback): ?string;

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array;
}
