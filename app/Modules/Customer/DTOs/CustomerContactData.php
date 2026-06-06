<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

final readonly class CustomerContactData
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
