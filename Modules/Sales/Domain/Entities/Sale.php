<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Entities;
use Modules\Sales\Domain\Enums\PaymentStatus;
use Modules\Sales\Domain\Enums\SaleStatus;
class Sale {
    public function __construct(
        private readonly int           $id,
        private readonly int           $tenantId,
        private readonly int           $organisationId,
        private string                 $invoiceNumber,
        private ?int                   $customerId,
        private SaleStatus             $saleStatus,
        private PaymentStatus          $paymentStatus,
        private string                 $subtotal,
        private string                 $discountAmount,
        private string                 $taxAmount,
        private string                 $total,
        private string                 $paidAmount,
        private string                 $dueAmount,
        private ?string                $saleDate,
        private ?string                $dueDate,
        private ?string                $notes,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrganisationId(): int { return $this->organisationId; }
    public function getInvoiceNumber(): string { return $this->invoiceNumber; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getSaleStatus(): SaleStatus { return $this->saleStatus; }
    public function getPaymentStatus(): PaymentStatus { return $this->paymentStatus; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getDiscountAmount(): string { return $this->discountAmount; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getTotal(): string { return $this->total; }
    public function getPaidAmount(): string { return $this->paidAmount; }
    public function getDueAmount(): string { return $this->dueAmount; }
    public function getSaleDate(): ?string { return $this->saleDate; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function calculateDue(): string {
        return bcsub($this->total, $this->paidAmount, 4);
    }
    public function applyPayment(string $amount): void {
        $this->paidAmount = bcadd($this->paidAmount, $amount, 4);
        $this->dueAmount  = bcsub($this->total, $this->paidAmount, 4);
        if (bccomp($this->dueAmount, '0', 4) <= 0) {
            $this->paymentStatus = PaymentStatus::PAID;
        } elseif (bccomp($this->paidAmount, '0', 4) > 0) {
            $this->paymentStatus = PaymentStatus::PARTIAL;
        }
    }
}
