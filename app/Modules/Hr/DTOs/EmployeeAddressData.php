<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\EmployeeAddressType;

final readonly class EmployeeAddressData
{
    public function __construct(public EmployeeAddressType $addressType, public string $addressLine1, public ?string $addressLine2 = null, public ?string $city = null, public ?string $state = null, public ?string $postalCode = null, public ?string $country = null, public bool $isPrimary = false, public bool $isActive = true) {}
}
