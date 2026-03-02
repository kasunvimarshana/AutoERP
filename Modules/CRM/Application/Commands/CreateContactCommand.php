<?php

declare(strict_types=1);

namespace Modules\Crm\Application\Commands;

final readonly class CreateContactCommand
{
    public function __construct(
        public int $tenantId,
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $company = null,
        public ?string $jobTitle = null,
        public ?string $notes = null,
    ) {}
}
