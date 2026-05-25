<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Contracts;

interface OrganizationUnitDomainServiceInterface
{
    public function ensureTenantId(int|string $tenantId): int;

    public function normalizeName(string $name): string;

    public function normalizeKey(string $key): string;

    public function normalizeOptionalText(?string $value, int $maxLength = 65535): ?string;

    /**
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(mixed $metadata): ?array;

    public function normalizeLevel(int $level): int;

    public function normalizeDepth(int $depth): int;
}