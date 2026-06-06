<?php

declare(strict_types=1);

namespace Modules\Sequence\Services\Rules;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Sequence\Constants\SequencePeriodType;
use Modules\Sequence\Services\Contracts\SequenceDomainServiceInterface;

final class SequenceDomainService implements SequenceDomainServiceInterface
{
    public function normalizeDocumentType(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('Document type is required.');
        }

        return $normalized;
    }

    public function normalizePeriodType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return SequencePeriodType::YEARLY;
        }

        if (! SequencePeriodType::isValid($normalized)) {
            throw new InvalidArgumentException('Period type must be yearly, monthly, or infinite.');
        }

        return $normalized;
    }

    public function normalizePeriodValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    public function normalizePadding(mixed $value): int
    {
        $padding = (int) $value;

        if ($padding < 1) {
            throw new InvalidArgumentException('Padding must be at least 1.');
        }

        return $padding;
    }

    public function normalizeNextNumber(mixed $value): int
    {
        $nextNumber = (int) $value;

        if ($nextNumber < 1) {
            throw new InvalidArgumentException('Next number must be at least 1.');
        }

        return $nextNumber;
    }

    public function resolvePeriodValue(string $periodType, ?string $periodValue, ?string $atDate = null): ?string
    {
        $normalizedPeriodType = $this->normalizePeriodType($periodType);
        $normalizedPeriodValue = $this->normalizePeriodValue($periodValue);

        if ($normalizedPeriodType === SequencePeriodType::INFINITE) {
            return null;
        }

        if ($normalizedPeriodValue !== null) {
            return $normalizedPeriodValue;
        }

        $date = $atDate === null || trim($atDate) === ''
            ? new DateTimeImmutable('now')
            : new DateTimeImmutable($atDate);

        if ($normalizedPeriodType === SequencePeriodType::MONTHLY) {
            return $date->format('Y-m');
        }

        return $date->format('Y');
    }

    public function scopeKey(?int $organizationUnitId, ?string $periodValue): string
    {
        $organizationScope = $organizationUnitId === null ? 'global' : (string) $organizationUnitId;
        $periodScope = $this->normalizePeriodValue($periodValue) ?? 'none';

        return $organizationScope.':'.$periodScope;
    }

    /**
     * @param  array<string, scalar|null>  $tokens
     */
    public function formatSequenceNumber(
        string $prefix,
        string $suffix,
        int $padding,
        int $number,
        array $tokens = [],
    ): string {
        if ($number < 1) {
            throw new InvalidArgumentException('Sequence number must be at least 1.');
        }

        $replacements = [];
        foreach ($tokens as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $replacements['{'.strtoupper(trim($key)).'}'] = (string) ($value ?? '');
        }

        $resolvedPrefix = strtr($prefix, $replacements);
        $resolvedSuffix = strtr($suffix, $replacements);

        return $resolvedPrefix.str_pad((string) $number, $padding, '0', STR_PAD_LEFT).$resolvedSuffix;
    }
}
