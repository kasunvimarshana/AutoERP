<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseInvoiceModel;

class EloquentPurchaseInvoiceRepository extends EloquentRepository implements PurchaseInvoiceRepositoryInterface
{
    public function __construct(PurchaseInvoiceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseInvoiceModel $m): PurchaseInvoice => $this->mapToDomain($m));
    }

    public function save(PurchaseInvoice $entity): PurchaseInvoice
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'grn_header_id' => $entity->getGrnHeaderId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'invoice_number' => $entity->getInvoiceNumber(),
            'supplier_invoice_number' => $entity->getSupplierInvoiceNumber(),
            'status' => $entity->getStatus(),
            'invoice_date' => $entity->getInvoiceDate()->format('Y-m-d'),
            'due_date' => $entity->getDueDate()->format('Y-m-d'),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'discount_total' => $entity->getDiscountTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'ap_account_id' => $entity->getApAccountId(),
            'journal_entry_id' => $entity->getJournalEntryId(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseInvoice
    {
        return parent::find($id, $columns);
    }

    private function mapToDomain(PurchaseInvoiceModel $m): PurchaseInvoice
    {
        return new PurchaseInvoice(
            tenantId: (int) $m->tenant_id,
            supplierId: (int) $m->supplier_id,
            invoiceNumber: (string) $m->invoice_number,
            status: (string) $m->status,
            invoiceDate: $m->invoice_date instanceof \DateTimeInterface ? $m->invoice_date : new \DateTimeImmutable((string) $m->invoice_date),
            dueDate: $m->due_date instanceof \DateTimeInterface ? $m->due_date : new \DateTimeImmutable((string) $m->due_date),
            currencyId: (int) $m->currency_id,
            exchangeRate: (string) $m->exchange_rate,
            grnHeaderId: $m->grn_header_id !== null ? (int) $m->grn_header_id : null,
            purchaseOrderId: $m->purchase_order_id !== null ? (int) $m->purchase_order_id : null,
            supplierInvoiceNumber: $m->supplier_invoice_number !== null ? (string) $m->supplier_invoice_number : null,
            subtotal: (string) $m->subtotal,
            taxTotal: (string) $m->tax_total,
            discountTotal: (string) $m->discount_total,
            grandTotal: (string) $m->grand_total,
            apAccountId: $m->ap_account_id !== null ? (int) $m->ap_account_id : null,
            journalEntryId: $m->journal_entry_id !== null ? (int) $m->journal_entry_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
