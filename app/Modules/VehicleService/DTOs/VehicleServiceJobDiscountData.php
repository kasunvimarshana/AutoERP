<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\DTOs;

use Modules\VehicleService\Enums\VehicleServiceDiscountCalculationType;

final readonly class VehicleServiceJobDiscountData
{
    public function __construct(
        public VehicleServiceDiscountCalculationType $calculationType,
        public string $rate,
        public string $fixedAmount,
        public string $reason,
        public ?int $changedBy = null,
    ) {}
}
