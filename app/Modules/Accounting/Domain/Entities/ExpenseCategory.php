<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class ExpenseCategory
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $accountId,
        public readonly ?string $parentId,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
