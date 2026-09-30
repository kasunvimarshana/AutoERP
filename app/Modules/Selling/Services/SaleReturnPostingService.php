<?php

declare(strict_types=1);

namespace Modules\Selling\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Idempotency\Enums\IdempotencyStatus;
use Modules\Idempotency\Services\IdempotencyService;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Services\StockMovementService;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Selling\Models\Sale;
use Modules\Selling\Models\SaleLine;
use Modules\Selling\Models\SaleReturn;
use Modules\Selling\Models\SaleReturnLine;

final class SaleReturnPostingService
{
    private const ZERO = '0.000000';

    private const IDEMPOTENCY_OPERATION = 'selling.return.create';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockMovementService $stockMovements,
        private readonly InvoiceBalanceService $invoiceBalances,
        private readonly IdempotencyService $idempotency,
    ) {}

    /** @param array<string, mixed> $payload */
    public function create(array $payload, string $idempotencyKey): Sale
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Return idempotency key is required.');
        }

        return DB::transaction(function () use ($payload, $idempotencyKey): Sale {
            $sale = Sale::query()->lockForUpdate()->findOrFail((int) $payload['sale_id']);
            if ((int) $sale->tenant_id !== (int) $payload['tenant_id']
                || $sale->organization_unit_id !== $payload['organization_unit_id']) {
                throw new InvalidArgumentException('Sale is outside the current organization.');
            }
            if ($sale->status !== 'posted' || $sale->invoice_id === null) {
                throw new InvalidArgumentException('Only completed sales with a posted invoice can be returned.');
            }
            if ((string) $payload['return_date'] < $sale->sale_date->toDateString()) {
                throw new InvalidArgumentException('Return date cannot be before the sale date.');
            }
            $invoice = Invoice::query()->lockForUpdate()->findOrFail((int) $sale->invoice_id);
            if (! in_array($invoice->status, [InvoiceStatus::Posted, InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid], true)) {
                throw new InvalidArgumentException('Only a posted customer invoice can receive a credit note.');
            }

            $idempotency = $this->idempotency->acquire(
                (int) $sale->tenant_id,
                $sale->organization_unit_id,
                self::IDEMPOTENCY_OPERATION,
                hash('sha256', $idempotencyKey),
                hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                $idempotencyKey,
                isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
            );
            if ($idempotency->status === IdempotencyStatus::Completed) {
                return $sale->refresh()->load(['customer', 'warehouse', 'lines.item', 'lines.uom', 'invoice.balance', 'returns.lines']);
            }
            if (! $idempotency->wasRecentlyCreated || $idempotency->status !== IdempotencyStatus::InProgress) {
                throw new InvalidArgumentException('Return request is already being processed.');
            }
            if ((int) $sale->row_version !== (int) $payload['expected_version']) {
                throw new InvalidArgumentException('Sale changed while this return was being prepared. Reload the sale and try again.');
            }

            $return = SaleReturn::query()->create([
                'tenant_id' => $sale->tenant_id,
                'organization_unit_id' => $sale->organization_unit_id,
                'sale_id' => $sale->getKey(),
                'invoice_id' => $invoice->getKey(),
                'return_number' => 'CRN-'.Str::ulid(),
                'return_date' => (string) $payload['return_date'],
                'reason' => trim((string) $payload['reason']),
                'created_by' => $payload['current_user_id'] ?? null,
            ]);
            $creditAmount = self::ZERO;
            foreach ($payload['lines'] as $lineData) {
                $line = SaleLine::query()->with(['item'])->lockForUpdate()->findOrFail((int) $lineData['sale_line_id']);
                if ((int) $line->sale_id !== (int) $sale->getKey() || (int) $line->tenant_id !== (int) $sale->tenant_id) {
                    throw new InvalidArgumentException('Selected return item does not belong to this sale.');
                }
                $alreadyReturned = $this->math->normalize((string) SaleReturnLine::query()
                    ->where('tenant_id', $sale->tenant_id)
                    ->where('sale_line_id', $line->getKey())
                    ->sum('quantity'));
                $remaining = $this->math->sub((string) $line->quantity, $alreadyReturned);
                $quantity = $this->math->normalize((string) $lineData['quantity']);
                if ($this->math->compare($quantity, $remaining) > 0) {
                    throw new InvalidArgumentException('Return quantity cannot exceed the unreturned quantity sold.');
                }

                $invoiceLine = $invoice->lines()
                    ->where('source_line_type', 'sale_line')
                    ->where('source_line_id', $line->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $credit = $this->math->mul(
                    $this->math->div($quantity, (string) $line->quantity, 12),
                    (string) $invoiceLine->line_total,
                );
                $baseQuantity = $this->math->mul(
                    $this->math->div($quantity, (string) $line->quantity, 12),
                    (string) $line->base_quantity,
                );
                $returnLine = SaleReturnLine::query()->create([
                    'tenant_id' => $sale->tenant_id,
                    'organization_unit_id' => $sale->organization_unit_id,
                    'sale_return_id' => $return->getKey(),
                    'sale_line_id' => $line->getKey(),
                    'quantity' => $quantity,
                    'credit_amount' => $credit,
                ]);
                $movement = $this->stockMovements->record(new StockMovementData(
                    tenantId: (int) $sale->tenant_id,
                    organizationUnitId: $sale->organization_unit_id,
                    movementDate: (string) $payload['return_date'],
                    movementType: InventoryMovementType::ReturnIn,
                    direction: InventoryDirection::In,
                    itemId: (int) $line->item_id,
                    itemVariantId: $line->item_variant_id === null ? null : (int) $line->item_variant_id,
                    warehouseId: (int) $sale->warehouse_id,
                    warehouseLocationId: $sale->warehouse_location_id === null ? null : (int) $sale->warehouse_location_id,
                    batchId: $line->batch_id === null ? null : (int) $line->batch_id,
                    serialNumberId: $line->serial_number_id === null ? null : (int) $line->serial_number_id,
                    quantity: $baseQuantity,
                    unitCost: (string) $line->unit_cost_snapshot,
                    sourceType: 'sale_return',
                    sourceId: (int) $return->getKey(),
                    sourceLineType: 'sale_return_line',
                    sourceLineId: (int) $returnLine->getKey(),
                    description: 'Stock returned for '.$return->return_number,
                    createdBy: isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null,
                    uomId: (int) $line->item->base_uom_id,
                ), isset($payload['current_user_id']) ? (int) $payload['current_user_id'] : null);
                $returnLine->inventory_movement_id = $movement->getKey();
                $returnLine->save();
                $creditAmount = $this->math->add($creditAmount, $credit);
            }

            $balance = $invoice->balance()->lockForUpdate()->firstOrFail();
            $creditApplied = $this->math->compare($creditAmount, (string) $balance->remaining_amount) < 0
                ? $creditAmount
                : (string) $balance->remaining_amount;
            if ($this->math->compare($creditApplied, self::ZERO) > 0) {
                $this->invoiceBalances->allocateCredit($invoice, 'sale_return', (int) $return->getKey(), $creditApplied);
            }
            $return->forceFill([
                'credit_amount' => $creditAmount,
                'credit_allocated_amount' => $creditApplied,
                'credit_available_amount' => $this->math->sub($creditAmount, $creditApplied),
                'posted_at' => now(),
            ])->save();
            $sale->forceFill(['row_version' => (int) $sale->row_version + 1])->save();
            $this->idempotency->complete(
                $idempotency,
                ['sale_id' => (int) $sale->getKey(), 'return_id' => (int) $return->getKey()],
                ['sale_id' => (int) $sale->getKey(), 'return_id' => (int) $return->getKey()],
            );

            return $sale->refresh()->load(['customer', 'warehouse', 'lines.item', 'lines.uom', 'invoice.balance', 'returns.lines']);
        }, 3);
    }
}
