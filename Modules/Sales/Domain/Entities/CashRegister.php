<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Entities;
class CashRegister {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private string          $name,
        private ?int            $locationId,
        private string          $openingBalance,
        private string          $currentBalance,
        private bool            $isOpen,
        private ?string         $openedAt,
        private ?string         $closedAt,
        private ?int            $openedBy,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCurrentBalance(): string { return $this->currentBalance; }
    public function isOpen(): bool { return $this->isOpen; }
    public function addCash(string $amount): void {
        $this->currentBalance = bcadd($this->currentBalance, $amount, 4);
    }
}
