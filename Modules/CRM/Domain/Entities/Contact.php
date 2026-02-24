<?php
namespace Modules\CRM\Domain\Entities;
class Contact
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $accountId,
        public readonly array $tags,
    ) {}
    public function fullName(): string { return "{$this->firstName} {$this->lastName}"; }
}
