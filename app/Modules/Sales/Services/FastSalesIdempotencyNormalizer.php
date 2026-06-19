<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;

final class FastSalesIdempotencyNormalizer
{
    /**
     * @var array<string, true>
     */
    private const DECIMAL_KEYS = [
        'quantity' => true,
        'unit_price' => true,
        'discount_amount' => true,
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
        unset($payload['current_user_id'], $payload['idempotency_key']);
        $normalized = $this->normalize($payload, null);

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    private function normalize(mixed $value, ?string $key): mixed
    {
        if (is_array($value)) {
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
}
