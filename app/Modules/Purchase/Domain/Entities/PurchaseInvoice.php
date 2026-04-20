<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseInvoice
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private ?int $grnHeaderId;

    private ?int $purchaseOrderId;

    private string $invoiceNumber;

    private ?string $supplierInvoiceNumber;

    private string $status;

    private \DateTimeInterface $invoiceDate;

    private \DateTimeInterface $dueDate;

    private int $currencyId;

    private string $exchangeRate;

    private string $subtotal;

    private string $taxTotal;

    private string $discountTotal;

    private string $grandTotal;

    private ?int $apAccountId;

    private ?int $journalEntryId;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        string $invoiceNumber,
        string $status,
        \DateTimeInterface $invoiceDate,
        \DateTimeInterface $dueDate,
        int $currencyId,
        string $exchangeRate,
        ?int $grnHeaderId = null,
        ?int $purchaseOrderId = null,
        ?string $supplierInvoiceNumber = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $discountTotal = '0',
        string $grandTotal = '0',
        ?int $apAccountId = null,
        ?int $journalEntryId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->invoiceNumber = $invoiceNumber;
        $this->status = $status;
        $this->invoiceDate = $invoiceDate;
        $this->dueDate = $dueDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->grnHeaderId = $grnHeaderId;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->supplierInvoiceNumber = $supplierInvoiceNumber;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->apAccountId = $apAccountId;
        $this->journalEntryId = $journalEntryId;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getGrnHeaderId(): ?int
    {
        return $this->grnHeaderId;
    }

    public function getPurchaseOrderId(): ?int
    {
        return $this->purchaseOrderId;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function getSupplierInvoiceNumber(): ?string
    {
        return $this->supplierInvoiceNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getInvoiceDate(): \DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->dueDate;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function getTaxTotal(): string
    {
        return $this->taxTotal;
    }

    public function getDiscountTotal(): string
    {
        return $this->discountTotal;
    }

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
    }

    public function getApAccountId(): ?int
    {
        return $this->apAccountId;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $supplierId,
        string $invoiceNumber,
        \DateTimeInterface $invoiceDate,
        \DateTimeInterface $dueDate,
        int $currencyId,
        string $exchangeRate,
        ?int $grnHeaderId = null,
        ?int $purchaseOrderId = null,
        ?string $supplierInvoiceNumber = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $discountTotal = '0',
        string $grandTotal = '0',
        ?int $apAccountId = null,
        ?int $journalEntryId = null,
    ): void {
        $this->supplierId = $supplierId;
        $this->invoiceNumber = $invoiceNumber;
        $this->invoiceDate = $invoiceDate;
        $this->dueDate = $dueDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->grnHeaderId = $grnHeaderId;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->supplierInvoiceNumber = $supplierInvoiceNumber;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->apAccountId = $apAccountId;
        $this->journalEntryId = $journalEntryId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function approve(): void
    {
        $this->status = 'approved';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
