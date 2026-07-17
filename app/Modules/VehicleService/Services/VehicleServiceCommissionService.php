<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class VehicleServiceCommissionService
{
    private const ZERO_AMOUNT = '0.000000';
    private const PERCENTAGE_BASE = '100.000000';

    public function __construct(private readonly DecimalMath $math) {}

    public function calculate(VehicleServiceCommissionType $type, string $value, string $base): string
    {
        $value = $this->math->normalize($value);
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException('Commission value cannot be negative.');
        }

        return match ($type) {
            VehicleServiceCommissionType::None => self::ZERO_AMOUNT,
            VehicleServiceCommissionType::Fixed => $value,
            VehicleServiceCommissionType::Percentage => $this->percentage($base, $value),
        };
    }

    /** @return list<string> */
    public function splitEvenly(string $amount, int $recipientCount): array
    {
        if ($recipientCount < 1) {
            throw new InvalidArgumentException('Commission must have at least one recipient.');
        }

        $amount = $this->math->normalize($amount);
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException('Commission amount cannot be negative.');
        }

        $share = $this->math->div($amount, (string) $recipientCount);
        $allocations = array_fill(0, $recipientCount, $share);
        $allocatedBeforeLast = $this->math->mul($share, (string) ($recipientCount - 1));
        $allocations[$recipientCount - 1] = $this->math->sub($amount, $allocatedBeforeLast);

        return $allocations;
    }

    private function percentage(string $base, string $rate): string
    {
        if ($this->math->compare($rate, self::PERCENTAGE_BASE) > 0) {
            throw new InvalidArgumentException('Commission percentage cannot exceed 100.');
        }

        return $this->math->div($this->math->mul($base, $rate), self::PERCENTAGE_BASE);
    }
}
