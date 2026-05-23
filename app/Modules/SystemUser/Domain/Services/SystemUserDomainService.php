<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;

class SystemUserDomainService
{
    /** @var array<int, string> */
    private const STATUSES = ['active', 'inactive', 'blocked'];

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeCode(?string $code): ?string
    {
        $normalized = $this->normalizeOptionalText($code);

        return $normalized === null ? null : Str::upper($normalized);
    }

    public function normalizeStatus(string $status): string
    {
        $normalized = Str::lower(trim($status));

        if (! in_array($normalized, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status must be active, inactive, or blocked.');
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }
}
