<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;

final class FastPurchaseIdempotencyNormalizer
{
    /**
     * @var array<string, true>
     */
    private const DECIMAL_KEYS = [
        'quantity' => true,
        'unit_cost' => true,
        'unit_price' => true,
        'discount' => true,
        'discount_rate' => true,
        'discount_amount' => true,
        'charge' => true,
        'charge_rate' => true,
        'charge_amount' => true,
        'tax' => true,
        'tax_rate' => true,
        'tax_amount' => true,
        'withholding_amount' => true,
        'exchange_rate' => true,
        'amount' => true,
        'payment_amount' => true,
        'allocated_amount' => true,
        'rate' => true,
    ];

    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hash(array $payload): string
    {
        unset($payload['current_user_id']);
        $normalized = $this->normalize($payload, null);

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    private function normalize(mixed $value, ?string $key): mixed
    {
        if (is_array($value)) {
            if ($key === 'lines') {
                return array_map(fn (mixed $row): mixed => $this->normalizeLine($row), array_values($value));
            }

            $isList = array_is_list($value);
            $normalized = [];
            foreach ($value as $childKey => $childValue) {
                if ($childKey === 'client_line_key') {
                    continue;
                }
                $normalized[$childKey] = $this->normalize($childValue, is_string($childKey) ? $childKey : null);
            }
            if (! $isList) {
                ksort($normalized);
            }

            return $normalized;
        }

        if ($value !== null && $key !== null && isset(self::DECIMAL_KEYS[$key])) {
            return $this->math->normalize((string) $value);
        }

        return $value;
    }

    private function normalizeLine(mixed $line): mixed
    {
        if (! is_array($line)) {
            return $line;
        }

        unset($line['client_line_key']);
        foreach (['discount_calculation_type', 'charge_calculation_type'] as $typeKey) {
            if (! array_key_exists($typeKey, $line)) {
                $line[$typeKey] = PurchaseAdjustmentCalculationType::Fixed->value;
            }
        }
        foreach (['discount_rate', 'discount_amount', 'charge_rate', 'charge_amount'] as $decimalKey) {
            if (! array_key_exists($decimalKey, $line)) {
                $line[$decimalKey] = '0.000000';
            }
        }

        return $this->normalize($line, null);
    }
}
