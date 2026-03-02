<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Entities;

use Modules\Crm\Domain\Enums\ContactStatus;

final class Contact
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $company,
        public readonly ?string $jobTitle,
        public readonly ContactStatus $status,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
