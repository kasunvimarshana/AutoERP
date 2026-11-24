<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\DTOs;

use Modules\Payroll\Enums\PayslipItemType;

final readonly class CalculatedLineItemDTO
{
    public function __construct(
        public PayslipItemType $itemType,
        public string $itemCode,
        public string $itemName,
        public string $rate,
        public string $hoursOrQuantity,
        public string $amount,
        public bool $isTaxable,
        public bool $isStatutory,
        public ?string $calculationBasis = null,
        public ?int $salaryComponentId = null,
    ) {}
}
