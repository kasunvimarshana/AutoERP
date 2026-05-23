<?php

declare(strict_types=1);

namespace Modules\Voucher\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Application\Repositories\TaxRateRepositoryInterface;
use Modules\Voucher\Domain\Exceptions\VoucherIntegrityException;

class VoucherDomainService
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRates) {}

    public function normalizeResourceKey(string $resource): string
    {
        return str_replace('-', '_', strtolower(trim($resource)));
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

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

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('voucher.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null, bool $uppercase = false): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = $uppercase ? strtoupper((string) $value) : strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw VoucherIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw VoucherIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("voucher.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw VoucherIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw VoucherIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareMoneyAttributes(array $attributes): array
    {
        $amount = (float) ($attributes['amount'] ?? 0);
        $tax = $this->tax($attributes);

        $attributes['tax_rate'] = $this->normalizeDecimal($tax['rate']);
        $attributes['amount'] = $this->normalizeDecimal($amount);
        $attributes['tax_amount'] = $this->normalizeDecimal($tax['amount']);
        $attributes['total_amount'] = $this->normalizeDecimal($amount + $tax['amount']);

        return $attributes;
    }

    public function nextRunDate(string $frequency, int $interval, string $currentDate): string
    {
        $date = CarbonImmutable::parse($currentDate);

        return match ($frequency) {
            'daily' => $date->addDays($interval)->toDateString(),
            'weekly' => $date->addWeeks($interval)->toDateString(),
            'quarterly' => $date->addMonths($interval * 3)->toDateString(),
            'yearly' => $date->addYears($interval)->toDateString(),
            default => $date->addMonths($interval)->toDateString(),
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{rate: float, amount: float}
     */
    private function tax(array $attributes): array
    {
        if (($attributes['tax_rate_id'] ?? null) !== null) {
            $taxRate = $this->taxRates->findById($attributes['tax_rate_id']);

            if ($taxRate !== null && isset($taxRate->rate)) {
                $rate = (float) $taxRate->rate;

                return [
                    'rate' => $rate,
                    'amount' => strtoupper((string) ($taxRate->type ?? 'PERCENTAGE')) === 'FIXED'
                        ? $rate
                        : (float) ($attributes['amount'] ?? 0) * ($rate / 100),
                ];
            }
        }

        $rate = (float) ($attributes['tax_rate'] ?? 0);

        return [
            'rate' => $rate,
            'amount' => (float) ($attributes['amount'] ?? 0) * ($rate / 100),
        ];
    }
}
