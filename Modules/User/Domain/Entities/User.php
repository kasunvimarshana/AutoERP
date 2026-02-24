<?php
namespace Modules\User\Domain\Entities;
class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $status,
        public readonly array $roles,
    ) {}
}
