<?php

namespace Modules\Logistics\Domain\Entities;

class Carrier
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $name,
        public readonly string  $code,
        public readonly ?string $contact_name,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly bool    $is_active,
        public readonly string  $created_at,
        public readonly string  $updated_at,
    ) {}
}
