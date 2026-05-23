<?php

declare(strict_types=1);

namespace Modules\Sequence\Domain\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SequenceDomainService
{
    /** @var array<int, string> */
    private const PERIOD_TYPES = ['yearly', 'monthly', 'infinite'];

    public function normalizeDocumentType(string $documentType): string
    {
        $value = Str::snake(trim($documentType));

        if ($value === '') {
            throw new InvalidArgumentException('Document type cannot be empty.');
        }

        return $value;
    }

    public function normalizePeriodType(string $periodType): string
    {
        $value = Str::lower(trim($periodType));

        if (! in_array($value, self::PERIOD_TYPES, true)) {
            throw new InvalidArgumentException('Period type must be yearly, monthly, or infinite.');
        }

        return $value;
    }

    public function normalizeText(?string $value): string
    {
        return $value === null ? '' : trim($value);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function resolvePeriodValue(string $periodType, ?DateTimeInterface $atDate = null): ?string
    {
        $normalized = $this->normalizePeriodType($periodType);
        $date = $atDate === null ? CarbonImmutable::now() : CarbonImmutable::instance($atDate);

        return match ($normalized) {
            'yearly' => $date->format('Y'),
            'monthly' => $date->format('Y-m'),
            'infinite' => null,
        };
    }

    public function formatNumber(string $prefix, int $number, int $padding, string $suffix): string
    {
        if ($padding < 1) {
            throw new InvalidArgumentException('Padding must be at least 1.');
        }

        if ($number < 1) {
            throw new InvalidArgumentException('Next number must be at least 1.');
        }

        return $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT) . $suffix;
    }
}
