<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class VehicleServiceCommissionService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function calculate(VehicleServiceCommissionType $type, string $value, string $base): string
    {
        $value = $this->math->normalize($value);
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException('Commission value cannot be negative.');
        }

        return match ($type) {
            VehicleServiceCommissionType::None => '0.000000',
            VehicleServiceCommissionType::Fixed => $value,
            VehicleServiceCommissionType::Percentage => $this->percentage($base, $value),
        };
    }

    private function percentage(string $base, string $rate): string
    {
        if ($this->math->compare($rate, '100.000000') > 0) {
            throw new InvalidArgumentException('Commission percentage cannot exceed 100.');
        }

        return $this->math->div($this->math->mul($base, $rate), '100.000000');
    }
}
