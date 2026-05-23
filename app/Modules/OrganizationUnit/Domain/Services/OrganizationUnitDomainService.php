<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Services;

class OrganizationUnitDomainService
{
    public function normalizeText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    public function normalizeCode(?string $code): ?string
    {
        $code = $this->normalizeText($code);

        return $code === null ? null : strtoupper($code);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizePath(?string $path): ?string
    {
        $path = $this->normalizeText($path);

        return $path === null ? null : trim($path, '/');
    }
}
