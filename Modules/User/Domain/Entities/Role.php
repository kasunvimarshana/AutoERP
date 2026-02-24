<?php
namespace Modules\User\Domain\Entities;
class Role
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $guardName,
        public readonly array $permissions,
    ) {}
}
