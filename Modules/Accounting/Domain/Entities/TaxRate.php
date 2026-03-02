<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
class TaxRate {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private string          $name,
        private string          $rate,
        private string          $type,
        private bool            $isActive,
        private bool            $isCompound,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getRate(): string { return $this->rate; }
    public function getType(): string { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
    public function isCompound(): bool { return $this->isCompound; }
    public function calculateTax(string $amount): string {
        if ($this->type === 'percentage') {
            return bcdiv(bcmul($amount, $this->rate, 4), '100', 4);
        }
        return bcadd($this->rate, '0', 4);
    }
}
