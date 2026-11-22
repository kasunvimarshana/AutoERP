<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\OrganizationUnit\Services\Contracts;

interface OrganizationUnitDomainServiceInterface
{
    public function ensureTenantId(int|string $tenantId): int;

    public function normalizeName(string $name): string;

    public function normalizeKey(string $key): string;

    public function normalizeOptionalText(?string $value, int $maxLength = 65535): ?string;

    public function normalizeLevel(int $level): int;

    public function normalizeDepth(int $depth): int;
}
