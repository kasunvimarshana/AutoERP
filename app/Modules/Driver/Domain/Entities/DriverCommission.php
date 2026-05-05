<?php declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

/**
 * DriverCommission Entity - Tracks commission earnings and calculations
 *
 * Manages commission records generated from rental transactions.
 */
final class DriverCommission
{
    private string $id;
    private string $tenantId;
    private string $driverId;
    private string $rentalTransactionId;
    private string $commissionAmount;
    private string $commissionPercentage;
    private \DateTime $earnedDate;
    private string $status; // pending, earned, paid, reversed
    private ?string $paymentId; // Reference to Finance module
    private \DateTime $createdAt;
    private ?\DateTime $paidAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $driverId,
        string $rentalTransactionId,
        string $commissionAmount,
        string $commissionPercentage,
        \DateTime $earnedDate,
        string $status = 'pending',
        ?string $paymentId = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->driverId = $driverId;
        $this->rentalTransactionId = $rentalTransactionId;
        $this->commissionAmount = $commissionAmount;
        $this->commissionPercentage = $commissionPercentage;
        $this->earnedDate = $earnedDate;
        $this->status = $status;
        $this->paymentId = $paymentId;
        $this->createdAt = new \DateTime();
        $this->paidAt = null;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getDriverId(): string { return $this->driverId; }
    public function getCommissionAmount(): string { return $this->commissionAmount; }
    public function getStatus(): string { return $this->status; }

    public function markAsPaid(string $paymentId): void
    {
        $this->status = 'paid';
        $this->paymentId = $paymentId;
        $this->paidAt = new \DateTime();
    }

    public function reverse(): void
    {
        $this->status = 'reversed';
    }
}
