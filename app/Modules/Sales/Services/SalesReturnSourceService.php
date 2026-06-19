<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\InvoiceLine;
use Modules\Sales\DTOs\ResolvedSalesReturnSource;
use Modules\Sales\DTOs\SalesReturnLineData;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturnLine;

final class SalesReturnSourceService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesOrderQuantityService $orderQuantities,
    ) {}

    public function resolve(SalesReturnLineData $line): ResolvedSalesReturnSource
    {
        if ($line->sourceLineType === null || $line->sourceLineId === null) {
            return $this->manualSource($line);
        }

        return match ($line->sourceLineType) {
            'sales_delivery_line' => $this->resolveDeliveryLine($line->sourceLineId),
            'sales_order_line' => $this->resolveOrderLine($line->sourceLineId),
            'invoice_line' => $this->resolveInvoiceLine($line->sourceLineId),
            default => throw new InvalidArgumentException(
                'Unsupported sales return source line type.',
            ),
        };
    }

    public function apply(SalesReturnLine $line): void
    {
        if ($line->source_line_type === null || $line->source_line_id === null) {
            return;
        }

        match ($line->source_line_type) {
            'sales_delivery_line' => $this->applyDeliveryLine(
                (int) $line->source_line_id,
                (string) $line->returned_quantity,
            ),
            'sales_order_line' => $this->applyOrderLine(
                (int) $line->source_line_id,
                (string) $line->returned_quantity,
            ),
            'invoice_line' => $this->applyInvoiceLine(
                (int) $line->source_line_id,
                (string) $line->returned_quantity,
                (int) $line->sales_return_id,
            ),
            default => throw new InvalidArgumentException(
                'Unsupported sales return source line type.',
            ),
        };
    }

    /**
     * @return Collection<int, SalesDeliveryLine>
     */
    public function returnableDeliveryLines(SalesDelivery $delivery): Collection
    {
        $delivery->loadMissing(['lines.item', 'lines.uom']);

        return $delivery->lines
            ->each(fn (SalesDeliveryLine $line) => $line->setAttribute(
                'returnable_quantity',
                $this->math->sub(
                    (string) $line->delivered_quantity,
                    (string) $line->returned_quantity,
                ),
            ))
            ->filter(fn (SalesDeliveryLine $line): bool => $this->math->compare(
                (string) $line->returnable_quantity,
                '0.000000',
            ) > 0)
            ->values();
    }

    private function manualSource(SalesReturnLineData $line): ResolvedSalesReturnSource
    {
        return new ResolvedSalesReturnSource(
            tenantId: null,
            organizationUnitId: null,
            customerId: null,
            itemId: $line->itemId,
            itemVariantId: $line->itemVariantId,
            uomId: $line->uomId,
            sourceQuantity: $line->returnedQuantity,
            previouslyReturnedQuantity: '0.000000',
            unitPrice: $line->unitPrice ?? $line->costBasis,
            discountAmount: '0.000000',
            taxAmount: '0.000000',
            chargeAmount: '0.000000',
        );
    }

    private function resolveDeliveryLine(int $lineId): ResolvedSalesReturnSource
    {
        $source = SalesDeliveryLine::query()
            ->with(['delivery', 'salesOrderLine'])
            ->findOrFail($lineId);

        return new ResolvedSalesReturnSource(
            tenantId: (int) $source->tenant_id,
            organizationUnitId: $source->organization_unit_id,
            customerId: (int) $source->delivery->customer_id,
            itemId: (int) $source->item_id,
            itemVariantId: $source->item_variant_id,
            uomId: $source->uom_id,
            sourceQuantity: (string) $source->delivered_quantity,
            previouslyReturnedQuantity: (string) $source->returned_quantity,
            unitPrice: (string) $source->unit_price,
            discountAmount: (string) ($source->salesOrderLine?->discount_amount ?? '0.000000'),
            taxAmount: (string) ($source->salesOrderLine?->tax_amount ?? '0.000000'),
            chargeAmount: (string) ($source->salesOrderLine?->charge_amount ?? '0.000000'),
        );
    }

    private function resolveOrderLine(int $lineId): ResolvedSalesReturnSource
    {
        $source = SalesOrderLine::query()->with('order')->findOrFail($lineId);

        return new ResolvedSalesReturnSource(
            tenantId: (int) $source->tenant_id,
            organizationUnitId: $source->organization_unit_id,
            customerId: (int) $source->order->customer_id,
            itemId: (int) $source->item_id,
            itemVariantId: $source->item_variant_id,
            uomId: $source->ordered_uom_id,
            sourceQuantity: $this->orderReturnBasis($source),
            previouslyReturnedQuantity: (string) $source->returned_quantity,
            unitPrice: (string) $source->unit_price,
            discountAmount: (string) $source->discount_amount,
            taxAmount: (string) $source->tax_amount,
            chargeAmount: (string) $source->charge_amount,
        );
    }

    private function resolveInvoiceLine(int $lineId): ResolvedSalesReturnSource
    {
        $source = InvoiceLine::query()->with('invoice')->findOrFail($lineId);

        return new ResolvedSalesReturnSource(
            tenantId: (int) $source->tenant_id,
            organizationUnitId: $source->organization_unit_id,
            customerId: $source->invoice->party_type === 'customer'
                ? (int) $source->invoice->party_id
                : null,
            itemId: $source->item_id,
            itemVariantId: null,
            uomId: $source->uom_id,
            sourceQuantity: (string) $source->quantity,
            previouslyReturnedQuantity: $this->postedInvoiceReturnQuantity($source),
            unitPrice: (string) $source->unit_price,
            discountAmount: (string) $source->discount_amount,
            taxAmount: (string) $source->tax_amount,
            chargeAmount: (string) $source->charge_amount,
        );
    }

    private function applyDeliveryLine(int $lineId, string $quantity): void
    {
        $line = SalesDeliveryLine::query()
            ->with(['delivery', 'salesOrderLine'])
            ->lockForUpdate()
            ->findOrFail($lineId);
        $this->assertRemaining(
            (string) $line->delivered_quantity,
            (string) $line->returned_quantity,
            $quantity,
        );

        $line->returned_quantity = $this->math->add(
            (string) $line->returned_quantity,
            $quantity,
        );
        $line->remaining_quantity = $this->math->sub(
            (string) $line->delivered_quantity,
            (string) $line->returned_quantity,
        );
        $line->status = $this->math->isZero((string) $line->remaining_quantity)
            ? 'returned'
            : 'partially_returned';
        $line->save();

        if ($line->salesOrderLine instanceof SalesOrderLine) {
            $this->orderQuantities->applyReturned($line->salesOrderLine, $quantity);
        }
        $this->refreshDeliveryStatus($line->delivery);
    }

    private function applyOrderLine(int $lineId, string $quantity): void
    {
        $line = SalesOrderLine::query()->lockForUpdate()->findOrFail($lineId);
        $this->assertRemaining(
            $this->orderReturnBasis($line),
            (string) $line->returned_quantity,
            $quantity,
        );
        $this->orderQuantities->applyReturned($line, $quantity);
    }

    private function applyInvoiceLine(
        int $lineId,
        string $quantity,
        int $salesReturnId,
    ): void {
        $line = InvoiceLine::query()->lockForUpdate()->findOrFail($lineId);
        $returnQuantity = (string) SalesReturnLine::query()
            ->where('sales_return_id', $salesReturnId)
            ->where('source_line_type', 'invoice_line')
            ->where('source_line_id', $lineId)
            ->sum('returned_quantity');
        $this->assertRemaining(
            (string) $line->quantity,
            $this->postedInvoiceReturnQuantity($line),
            $returnQuantity,
        );

        if ($line->source_line_type === 'sales_delivery_line'
            && $line->source_line_id !== null) {
            $this->applyDeliveryLine((int) $line->source_line_id, $quantity);
        } elseif ($line->source_line_type === 'sales_order_line'
            && $line->source_line_id !== null) {
            $this->applyOrderLine((int) $line->source_line_id, $quantity);
        }
    }

    private function assertRemaining(
        string $sourceQuantity,
        string $returnedQuantity,
        string $requestedQuantity,
    ): void {
        $remaining = $this->math->sub($sourceQuantity, $returnedQuantity);
        if ($this->math->compare($requestedQuantity, $remaining) > 0) {
            throw new InvalidArgumentException(
                'Returned quantity cannot exceed source remaining quantity.',
            );
        }
    }

    private function postedInvoiceReturnQuantity(InvoiceLine $line): string
    {
        return (string) SalesReturnLine::query()
            ->where('source_line_type', 'invoice_line')
            ->where('source_line_id', $line->getKey())
            ->whereHas(
                'salesReturn',
                fn ($query) => $query->where('status', SalesReturnStatus::Posted),
            )
            ->sum('returned_quantity');
    }

    private function orderReturnBasis(SalesOrderLine $line): string
    {
        return $this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
            ? (string) $line->delivered_quantity
            : (string) $line->ordered_quantity;
    }

    private function refreshDeliveryStatus(SalesDelivery $delivery): void
    {
        $delivery->updated_at = now();
        $delivery->save();
    }
}
