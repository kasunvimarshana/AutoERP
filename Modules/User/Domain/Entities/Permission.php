<?php
namespace Modules\User\Domain\Entities;
class Permission
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $guardName,
        public readonly string $module,
        public readonly string $action,
    ) {}
}
