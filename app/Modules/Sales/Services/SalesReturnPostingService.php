<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\DTOs\SalesCreditNoteData;
use Modules\Sales\DTOs\SalesDeliveryLineData;
use Modules\Sales\DTOs\SalesPostingResult;
use Modules\Sales\Enums\SalesCreditNoteStatus;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Enums\SalesReturnType;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesReturn;
use Modules\Tax\Services\TaxReturnAllocationService;

final class SalesReturnPostingService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesInventoryIntegrationService $inventory,
        private readonly SalesCreditNoteService $creditNotes,
        private readonly SalesStatusService $statuses,
        private readonly SalesReturnSourceService $sources,
        private readonly SalesDeliveryService $deliveries,
        private readonly TaxReturnAllocationService $taxReturns,
    ) {}

    public function post(SalesReturn $return, ?int $userId = null): SalesPostingResult
    {
        return DB::transaction(function () use ($return, $userId): SalesPostingResult {
            $return = SalesReturn::query()
                ->lockForUpdate()
                ->findOrFail($return->getKey());
            $this->assertPostable($return);
            $return->load('lines');

            $movementIds = [];
            foreach ($return->lines as $line) {
                $movement = $this->inventory->returnIn($return, $line, $userId);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                    $movementIds[] = (int) $movement->getKey();
                    $line->save();
                }
                $this->sources->apply($line);
            }

            $replacementDelivery = $this->dispatchReplacement($return, $userId);
            if ($replacementDelivery !== null) {
                foreach ($replacementDelivery->lines as $line) {
                    if ($line->inventory_movement_id !== null) {
                        $movementIds[] = (int) $line->inventory_movement_id;
                    }
                }
            }

            $creditNote = $this->createCreditNote($return);
            $this->statuses->transition($return, SalesReturnStatus::Posted, $userId);
            $return->credit_note_id = $creditNote?->getKey();
            $return->posted_by = $userId;
            $return->posted_at = now();
            $return->save();
            $this->taxReturns->reverseSalesReturn(
                $return->refresh()->load('lines'),
                $creditNote === null ? null : (int) $creditNote->getKey(),
            );

            return new SalesPostingResult(
                (int) $return->getKey(),
                (string) $return->return_number,
                SalesReturnStatus::Posted->value,
                $movementIds,
                creditNoteId: $creditNote?->getKey(),
            );
        });
    }

    private function assertPostable(SalesReturn $return): void
    {
        if ($return->status === SalesReturnStatus::Posted) {
            throw new InvalidArgumentException('Posted sales returns are immutable.');
        }
        if ((bool) $return->approval_required
            && $return->status !== SalesReturnStatus::Approved) {
            throw new InvalidArgumentException(
                'Sales return must be approved before posting.',
            );
        }
    }

    private function createCreditNote(SalesReturn $return): ?SalesCreditNote
    {
        if (! (bool) $return->affects_customer_balance
            || $this->math->compare((string) $return->grand_total, '0.000000') <= 0) {
            return null;
        }

        $creditNote = $this->creditNotes->create(new SalesCreditNoteData(
            tenantId: (int) $return->tenant_id,
            creditNoteDate: $return->return_date->toDateString(),
            customerId: (int) $return->customer_id,
            amount: (string) $return->grand_total,
            organizationUnitId: $return->organization_unit_id,
            salesReturnId: (int) $return->getKey(),
            reason: $return->reason ?: 'Sales return '.$return->return_number,
        ));
        $creditNote->status = SalesCreditNoteStatus::Posted;
        $creditNote->save();

        return $creditNote;
    }

    private function dispatchReplacement(
        SalesReturn $return,
        ?int $userId,
    ): ?SalesDelivery {
        if (! in_array($return->return_type, [
            SalesReturnType::WarrantyReplacement,
            SalesReturnType::ExchangeReturn,
        ], true)) {
            return null;
        }

        $order = SalesOrder::query()
            ->with('lines')
            ->findOrFail($return->replacement_sales_order_id);
        if ($order->warehouse_id === null) {
            throw new InvalidArgumentException(
                'Replacement sales order requires a warehouse before the return can be posted.',
            );
        }

        $lines = [];
        foreach ($order->lines as $line) {
            if ($this->math->compare(
                (string) $line->remaining_deliverable_quantity,
                '0.000000',
            ) <= 0) {
                continue;
            }

            $lines[] = new SalesDeliveryLineData(
                itemId: (int) $line->item_id,
                deliveredQuantity: (string) $line->remaining_deliverable_quantity,
                unitPrice: (string) $line->unit_price,
                salesOrderLineId: (int) $line->getKey(),
                itemVariantId: $line->item_variant_id,
                description: $line->description,
                uomId: $line->ordered_uom_id,
                orderedQuantity: (string) $line->ordered_quantity,
            );
        }
        if ($lines === []) {
            throw new InvalidArgumentException(
                'Replacement sales order has no deliverable quantities.',
            );
        }

        $delivery = $this->deliveries->create(new CreateSalesDeliveryData(
            tenantId: (int) $return->tenant_id,
            deliveryDate: $return->return_date->toDateString(),
            customerId: (int) $return->customer_id,
            warehouseId: (int) $order->warehouse_id,
            organizationUnitId: $return->organization_unit_id,
            salesOrderId: (int) $order->getKey(),
            warehouseLocationId: $order->warehouse_location_id,
            notes: ucfirst(str_replace('_', ' ', $return->return_type->value))
                .' for '.$return->return_number,
            deliveredBy: $userId,
            lines: $lines,
        ));

        return $this->deliveries->post($delivery, $userId);
    }
}
