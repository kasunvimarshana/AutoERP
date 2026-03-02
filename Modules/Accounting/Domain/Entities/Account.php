<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
use Modules\Accounting\Domain\Enums\AccountType;
class Account {
    public function __construct(
        private readonly int         $id,
        private readonly int         $tenantId,
        private string               $code,
        private string               $name,
        private AccountType          $type,
        private ?int                 $parentId,
        private bool                 $isActive,
        private readonly string      $normalBalance,
        private string               $currentBalance = '0.0000',
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): AccountType { return $this->type; }
    public function getParentId(): ?int { return $this->parentId; }
    public function isActive(): bool { return $this->isActive; }
    public function getNormalBalance(): string { return $this->normalBalance; }
    public function getCurrentBalance(): string { return $this->currentBalance; }
    public function isDebitNormal(): bool { return $this->normalBalance === 'debit'; }
    public function isCreditNormal(): bool { return $this->normalBalance === 'credit'; }
}
