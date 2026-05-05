<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Entities\SalesReturnLine;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;

class EloquentSalesReturnRepository extends EloquentRepository implements SalesReturnRepositoryInterface
{
    public function __construct(SalesReturnModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SalesReturnModel $m): SalesReturn => $this->mapModelToDomainEntity($m));
    }

    public function save(SalesReturn $return): SalesReturn
    {
        return DB::transaction(function () use ($return): SalesReturn {
            $data = [
                'tenant_id' => $return->getTenantId(),
                'customer_id' => $return->getCustomerId(),
                'original_sales_order_id' => $return->getOriginalSalesOrderId(),
                'original_invoice_id' => $return->getOriginalInvoiceId(),
                'return_number' => $return->getReturnNumber(),
                'status' => $return->getStatus(),
                'return_date' => $return->getReturnDate()->format('Y-m-d'),
                'return_reason' => $return->getReturnReason(),
                'currency_id' => $return->getCurrencyId(),
                'exchange_rate' => $return->getExchangeRate(),
                'subtotal' => $return->getSubtotal(),
                'tax_total' => $return->getTaxTotal(),
                'restocking_fee_total' => $return->getRestockingFeeTotal(),
                'grand_total' => $return->getGrandTotal(),
                'credit_memo_number' => $return->getCreditMemoNumber(),
                'journal_entry_id' => $return->getJournalEntryId(),
                'notes' => $return->getNotes(),
                'metadata' => $return->getMetadata(),
            ];

            if ($return->getId()) {
                $model = $this->update($return->getId(), $data);
            } else {
                $model = $this->create($data);
            }

            /** @var SalesReturnModel $model */
            $keptLineIds = [];
            foreach ($return->getLines() as $line) {
                $lineData = [
                    'tenant_id' => (int) $model->tenant_id,
                    'original_sales_order_line_id' => $line->getOriginalSalesOrderLineId(),
                    'product_id' => $line->getProductId(),
                    'variant_id' => $line->getVariantId(),
                    'batch_id' => $line->getBatchId(),
                    'serial_id' => $line->getSerialId(),
                    'to_location_id' => $line->getToLocationId(),
                    'uom_id' => $line->getUomId(),
                    'return_qty' => $line->getReturnQty(),
                    'unit_price' => $line->getUnitPrice(),
                    'condition' => $line->getCondition(),
                    'disposition' => $line->getDisposition(),
                    'restocking_fee' => $line->getRestockingFee(),
                    'quality_check_notes' => $line->getQualityCheckNotes(),
                ];

                $lineId = $line->getId();
                if ($lineId !== null) {
                    $updated = $model->lines()
                        ->where('tenant_id', (int) $model->tenant_id)
                        ->whereKey($lineId)
                        ->update($lineData);

                    if ($updated > 0) {
                        $keptLineIds[] = $lineId;
                        continue;
                    }
                }

                $createdLine = $model->lines()->create($lineData);
                $keptLineIds[] = (int) $createdLine->id;
            }

            $lineCleanupQuery = $model->lines()->where('tenant_id', (int) $model->tenant_id);
            if ($keptLineIds === []) {
                $lineCleanupQuery->delete();
            } else {
                $lineCleanupQuery->whereNotIn('id', $keptLineIds)->delete();
            }

            $model->load('lines');

            return $this->toDomainEntity($model);
        });
    }

    public function find(int|string $id, array $columns = ['*']): ?SalesReturn
    {
        /** @var SalesReturnModel|null $model */
        $model = $this->newScopedQuery()->with('lines')->find($id, $columns);

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndReturnNumber(int $tenantId, string $returnNumber): ?SalesReturn
    {
        /** @var SalesReturnModel|null $model */
        $model = $this->model->newQuery()->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('return_number', $returnNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(SalesReturnModel $model): SalesReturn
    {
        $return = new SalesReturn(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            currencyId: (int) $model->currency_id,
            returnDate: new \DateTimeImmutable((string) $model->return_date),
            originalSalesOrderId: $model->original_sales_order_id !== null ? (int) $model->original_sales_order_id : null,
            originalInvoiceId: $model->original_invoice_id !== null ? (int) $model->original_invoice_id : null,
            returnNumber: $model->return_number,
            status: (string) $model->status,
            returnReason: $model->return_reason,
            exchangeRate: (string) $model->exchange_rate,
            subtotal: (string) $model->subtotal,
            taxTotal: (string) $model->tax_total,
            restockingFeeTotal: (string) $model->restocking_fee_total,
            grandTotal: (string) $model->grand_total,
            creditMemoNumber: $model->credit_memo_number,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        $lines = [];
        foreach ($model->lines ?? [] as $lineModel) {
            $lines[] = new SalesReturnLine(
                tenantId: (int) $lineModel->tenant_id,
                productId: (int) $lineModel->product_id,
                toLocationId: (int) $lineModel->to_location_id,
                uomId: (int) $lineModel->uom_id,
                salesReturnId: (int) $lineModel->sales_return_id,
                originalSalesOrderLineId: $lineModel->original_sales_order_line_id !== null
                    ? (int) $lineModel->original_sales_order_line_id
                    : null,
                variantId: $lineModel->variant_id !== null ? (int) $lineModel->variant_id : null,
                batchId: $lineModel->batch_id !== null ? (int) $lineModel->batch_id : null,
                serialId: $lineModel->serial_id !== null ? (int) $lineModel->serial_id : null,
                returnQty: (string) $lineModel->return_qty,
                unitPrice: (string) $lineModel->unit_price,
                lineTotal: (string) $lineModel->line_total,
                condition: (string) $lineModel->condition,
                disposition: (string) $lineModel->disposition,
                restockingFee: (string) $lineModel->restocking_fee,
                qualityCheckNotes: $lineModel->quality_check_notes,
                id: (int) $lineModel->id,
            );
        }
        $return->setLines($lines);

        return $return;
    }
}
