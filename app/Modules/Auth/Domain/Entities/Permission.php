<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use DateTimeInterface;

class Permission
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $guard,
        public readonly string $module,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
