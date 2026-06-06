<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Rules;

use InvalidArgumentException;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;

final class OrganizationUnitDomainService implements OrganizationUnitDomainServiceInterface
{
    public function ensureTenantId(int|string $tenantId): int
    {
        $value = (int) $tenantId;
        if ($value < 1) {
            throw new InvalidArgumentException('Tenant ID must be greater than zero.');
        }

        return $value;
    }

    public function normalizeName(string $name): string
    {
        $value = trim($name);
        if ($value === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Name may not be greater than 255 characters.');
        }

        return $value;
    }

    public function normalizeKey(string $key): string
    {
        $value = trim($key);
        if ($value === '') {
            throw new InvalidArgumentException('Key is required.');
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Key may not be greater than 255 characters.');
        }

        return $value;
    }

    public function normalizeOptionalText(?string $value, int $maxLength = 65535): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        if (mb_strlen($normalized) > $maxLength) {
            throw new InvalidArgumentException(sprintf('Value may not exceed %d characters.', $maxLength));
        }

        return $normalized;
    }

    public function normalizeMetadata(mixed $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        throw new InvalidArgumentException('Metadata must be an array or null.');
    }

    public function normalizeLevel(int $level): int
    {
        if ($level < 0) {
            throw new InvalidArgumentException('Level cannot be negative.');
        }

        return $level;
    }

    public function normalizeDepth(int $depth): int
    {
        if ($depth < 0) {
            throw new InvalidArgumentException('Depth cannot be negative.');
        }

        return $depth;
    }
}
