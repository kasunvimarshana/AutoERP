<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\AccountStatus;
use Modules\Accounting\Domain\Enums\AccountType;

final class Account
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly AccountType $type,
        public readonly AccountStatus $status,
        public readonly ?string $description,
        public readonly bool $isSystemAccount,
        public readonly string $openingBalance,
        public readonly string $currentBalance,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
