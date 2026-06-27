<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

use Modules\Finance\Enums\NormalBalance;

final readonly class CreateAccountData
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public int $tenantId,
        public int $accountTypeId,
        public string $code,
        public string $name,
        public NormalBalance $normalBalance,
        public ?int $organizationUnitId = null,
        public ?int $accountCategoryId = null,
        public ?int $parentId = null,
        public ?string $description = null,
        public bool $isControlAccount = false,
        public bool $isPostingAccount = true,
        public bool $isCashAccount = false,
        public bool $isBankAccount = false,
        public bool $isTaxAccount = false,
        public bool $isSystem = false,
        public bool $isActive = true,
        public ?array $metadata = null,
        public ?int $expectedVersion = null,
    ) {}

    public function forOrganizationUnit(?int $organizationUnitId): self
    {
        return new self(
            tenantId: $this->tenantId,
            accountTypeId: $this->accountTypeId,
            code: $this->code,
            name: $this->name,
            normalBalance: $this->normalBalance,
            organizationUnitId: $organizationUnitId,
            accountCategoryId: $this->accountCategoryId,
            parentId: $this->parentId,
            description: $this->description,
            isControlAccount: $this->isControlAccount,
            isPostingAccount: $this->isPostingAccount,
            isCashAccount: $this->isCashAccount,
            isBankAccount: $this->isBankAccount,
            isTaxAccount: $this->isTaxAccount,
            isSystem: $this->isSystem,
            isActive: $this->isActive,
            metadata: $this->metadata,
            expectedVersion: $this->expectedVersion,
        );
    }
}
