<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Entities;

/**
 * Chart-of-Accounts entry domain entity.
 */
class Account
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly ?string $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $subType,
        public readonly string $normalBalance,
        public readonly string $currencyCode,
        public readonly bool $isActive,
        public readonly bool $isSystem,
    ) {}
}
