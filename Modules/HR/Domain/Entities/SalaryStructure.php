<?php

namespace Modules\HR\Domain\Entities;

class SalaryStructure
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly string             $name,
        public readonly string             $code,
        public readonly bool               $is_active,
        public readonly ?string            $description,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
