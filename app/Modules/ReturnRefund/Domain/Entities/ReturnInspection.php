<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Entities;

final class ReturnInspection
{
    private string $id;
    private string $tenantId;
    private string $rentalTransactionId;
    private bool $isDamaged;
    private string $damageNotes;
    private string $damageCharge;
    private string $fuelAdjustmentCharge;
    private string $lateReturnCharge;
    private \DateTime $inspectedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $rentalTransactionId,
        bool $isDamaged,
        string $damageNotes,
        string $damageCharge,
        string $fuelAdjustmentCharge,
        string $lateReturnCharge,
        \DateTime $inspectedAt,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->rentalTransactionId = $rentalTransactionId;
        $this->isDamaged = $isDamaged;
        $this->damageNotes = $damageNotes;
        $this->damageCharge = $damageCharge;
        $this->fuelAdjustmentCharge = $fuelAdjustmentCharge;
        $this->lateReturnCharge = $lateReturnCharge;
        $this->inspectedAt = $inspectedAt;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getRentalTransactionId(): string { return $this->rentalTransactionId; }
    public function isDamaged(): bool { return $this->isDamaged; }
    public function getDamageNotes(): string { return $this->damageNotes; }
    public function getDamageCharge(): string { return $this->damageCharge; }
    public function getFuelAdjustmentCharge(): string { return $this->fuelAdjustmentCharge; }
    public function getLateReturnCharge(): string { return $this->lateReturnCharge; }
    public function getInspectedAt(): \DateTime { return $this->inspectedAt; }

    public function totalAdjustments(): string
    {
        return bcadd(
            bcadd($this->damageCharge, $this->fuelAdjustmentCharge, 6),
            $this->lateReturnCharge,
            6
        );
    }
}
