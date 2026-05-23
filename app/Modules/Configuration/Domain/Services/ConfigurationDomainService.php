<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Services;

use InvalidArgumentException;

class ConfigurationDomainService
{
    public function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function normalizeText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function assertCurrencyDecimalPlaces(int $decimalPlaces): void
    {
        if ($decimalPlaces < 0 || $decimalPlaces > 8) {
            throw new InvalidArgumentException('Currency decimal places must be between 0 and 8.');
        }
    }
}
