<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $subType,
        public readonly string $normalBalance,
        public readonly string $currencyCode,
        public readonly bool $isActive,
        public readonly bool $isLocked,
        public readonly bool $isSystemAccount,
        public readonly ?string $description,
        public readonly string $path,
        public readonly int $level,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isDebitNormalBalance(): bool
    {
        return $this->normalBalance === 'debit';
    }

    public function canHaveBalance(): bool
    {
        return !$this->isLocked;
    }

    public function isParent(): bool
    {
        return $this->parentId === null;
    }
}
