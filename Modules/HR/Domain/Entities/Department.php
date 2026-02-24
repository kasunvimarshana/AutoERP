<?php

namespace Modules\HR\Domain\Entities;

class Department
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly string             $name,
        public readonly ?string            $description,
        public readonly ?string            $manager_id,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
