<?php
namespace Modules\CRM\Domain\Entities;
class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly ?string $industry,
        public readonly ?string $website,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly array $address,
        public readonly array $tags,
        public readonly ?string $accountManagerId,
    ) {}
}
