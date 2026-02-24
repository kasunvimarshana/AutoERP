<?php

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\AccountType;

class Account
{
    public function __construct(
        private readonly string           $id,
        private readonly string           $tenantId,
        private readonly string           $code,
        private readonly string           $name,
        private readonly AccountType      $type,
        private readonly ?string          $parentId,
        private readonly bool             $isActive,
        private readonly string           $balance,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): AccountType { return $this->type; }
    public function getParentId(): ?string { return $this->parentId; }
    public function isActive(): bool { return $this->isActive; }
    public function getBalance(): string { return $this->balance; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
