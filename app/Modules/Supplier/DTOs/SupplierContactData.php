<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\DTOs;

final readonly class SupplierContactData
{
    public function __construct(
        public string $contactName,
        public ?string $designation = null,
        public ?string $department = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public bool $isPrimary = false,
        public bool $isActive = true,
        public ?string $notes = null,
    ) {}
}
