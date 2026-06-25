<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantDomainServiceInterface
{
    public function normalizeCode(string $value): string;
    public function normalizeName(string $value): string;
    public function normalizeSlug(string $value): string;
    public function normalizeDomain(string $value): string;
    public function normalizeBillingInterval(?string $value): string;
    public function normalizeOptionalText(?string $value): ?string;
    /** @return array<string, mixed> */
    public function normalizeMetadata(mixed $value): array;
}
