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

use Modules\VehicleService\Enums\VehicleServiceCustomerStatus;
use Modules\VehicleService\Enums\VehicleServiceCustomerType;
use Modules\VehicleService\Enums\VehicleServiceOwnerType;

final readonly class VehicleServiceCustomerData
{
    public function __construct(
        public int $id,
        public string $customerNumber,
        public string $code,
        public string $name,
        public VehicleServiceCustomerType $customerType,
        public VehicleServiceCustomerStatus $status,
        public VehicleServiceOwnerType $ownerType,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public ?int $currencyId = null,
        public ?string $creditLimit = null,
        public bool $creditAllowed = false,
        public bool $advanceAllowed = true,
        public ?int $organizationUnitId = null,
        public ?string $organizationUnitName = null,
    ) {}
}
