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

final readonly class EmployeeContactData
{
    public function __construct(public string $contactName, public ?string $relationship = null, public ?string $email = null, public ?string $phone = null, public ?string $mobile = null, public bool $isEmergencyContact = false, public bool $isPrimary = false, public bool $isActive = true, public ?string $notes = null) {}
}
