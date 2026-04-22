<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesInvoiceLine;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesInvoiceModel;

class EloquentSalesInvoiceRepository extends EloquentRepository implements SalesInvoiceRepositoryInterface
{
    public function __construct(SalesInvoiceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SalesInvoiceModel $m): SalesInvoice => $this->mapModelToDomainEntity($m));
    }

    public function save(SalesInvoice $invoice): SalesInvoice
    {
        return DB::transaction(function () use ($invoice): SalesInvoice {
            $data = [
                'tenant_id' => $invoice->getTenantId(),
                'customer_id' => $invoice->getCustomerId(),
                'sales_order_id' => $invoice->getSalesOrderId(),
                'shipment_id' => $invoice->getShipmentId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'status' => $invoice->getStatus(),
                'invoice_date' => $invoice->getInvoiceDate()->format('Y-m-d'),
                'due_date' => $invoice->getDueDate()->format('Y-m-d'),
                'currency_id' => $invoice->getCurrencyId(),
                'exchange_rate' => $invoice->getExchangeRate(),
                'subtotal' => $invoice->getSubtotal(),
                'tax_total' => $invoice->getTaxTotal(),
                'discount_total' => $invoice->getDiscountTotal(),
                'grand_total' => $invoice->getGrandTotal(),
                'paid_amount' => $invoice->getPaidAmount(),
                'ar_account_id' => $invoice->getArAccountId(),
                'journal_entry_id' => $invoice->getJournalEntryId(),
                'notes' => $invoice->getNotes(),
                'metadata' => $invoice->getMetadata(),
            ];

            if ($invoice->getId()) {
                $model = $this->update($invoice->getId(), $data);
            } else {
                $model = $this->create($data);
            }

            /** @var SalesInvoiceModel $model */
            $model->lines()->delete();
            foreach ($invoice->getLines() as $line) {
                $model->lines()->create([
                    'tenant_id' => $line->getTenantId(),
                    'sales_order_line_id' => $line->getSalesOrderLineId(),
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'description' => $line->getDescription(),
                    'uom_id' => $line->getUomId(),
                    'quantity' => $line->getQuantity(),
                    'unit_price' => $line->getUnitPrice(),
                    'discount_pct' => $line->getDiscountPct(),
                    'tax_group_id' => $line->getTaxGroupId(),
                    'tax_amount' => $line->getTaxAmount(),
                    'line_total' => $line->getLineTotal(),
                    'income_account_id' => $line->getIncomeAccountId(),
                ]);
            }

            $model->load('lines');

            return $this->toDomainEntity($model);
        });
    }

    public function find(int|string $id, array $columns = ['*']): ?SalesInvoice
    {
        /** @var SalesInvoiceModel|null $model */
        $model = $this->model->newQuery()->with('lines')->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndInvoiceNumber(int $tenantId, string $invoiceNumber): ?SalesInvoice
    {
        /** @var SalesInvoiceModel|null $model */
        $model = $this->model->newQuery()->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', $invoiceNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(SalesInvoiceModel $model): SalesInvoice
    {
        $invoice = new SalesInvoice(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            currencyId: (int) $model->currency_id,
            invoiceDate: new \DateTimeImmutable((string) $model->invoice_date),
            dueDate: new \DateTimeImmutable((string) $model->due_date),
            salesOrderId: $model->sales_order_id !== null ? (int) $model->sales_order_id : null,
            shipmentId: $model->shipment_id !== null ? (int) $model->shipment_id : null,
            invoiceNumber: $model->invoice_number,
            status: (string) $model->status,
            exchangeRate: (string) $model->exchange_rate,
            subtotal: (string) $model->subtotal,
            taxTotal: (string) $model->tax_total,
            discountTotal: (string) $model->discount_total,
            grandTotal: (string) $model->grand_total,
            arAccountId: $model->ar_account_id !== null ? (int) $model->ar_account_id : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            paidAmount: (string) ($model->paid_amount ?? '0.000000'),
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        $lines = [];
        foreach ($model->lines ?? [] as $lineModel) {
            $lines[] = new SalesInvoiceLine(
                tenantId: (int) $lineModel->tenant_id,
                productId: (int) $lineModel->product_id,
                uomId: (int) $lineModel->uom_id,
                salesInvoiceId: (int) $lineModel->sales_invoice_id,
                salesOrderLineId: $lineModel->sales_order_line_id !== null ? (int) $lineModel->sales_order_line_id : null,
                variantId: $lineModel->variant_id !== null ? (int) $lineModel->variant_id : null,
                description: $lineModel->description,
                quantity: (string) $lineModel->quantity,
                unitPrice: (string) $lineModel->unit_price,
                discountPct: (string) $lineModel->discount_pct,
                taxGroupId: $lineModel->tax_group_id !== null ? (int) $lineModel->tax_group_id : null,
                taxAmount: (string) $lineModel->tax_amount,
                lineTotal: (string) $lineModel->line_total,
                incomeAccountId: $lineModel->income_account_id !== null ? (int) $lineModel->income_account_id : null,
                id: (int) $lineModel->id,
            );
        }
        $invoice->setLines($lines);

        return $invoice;
    }
}
