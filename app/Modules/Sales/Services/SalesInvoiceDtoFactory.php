<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Sales\DTOs\CreateSalesInvoiceData;
use Modules\Sales\DTOs\PreparedSalesInvoiceData;
use Modules\Sales\DTOs\SalesInvoiceSourceData;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesHeaderAdjustment;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

final class SalesInvoiceDtoFactory
{
    public function __construct(private readonly DecimalMath $math) {}

    public function prepare(
        CreateSalesInvoiceData $data,
        bool $lockSources = false,
    ): PreparedSalesInvoiceData {
        $invoiceLines = [];
        $sources = [];
        $sourceLines = [];
        $sourceTotals = [];
        $lineQuantities = [];
        $deliveries = collect();
        $lineNumber = 1;
        $resolvedCustomerId = $data->customerId;
        $adjustments = $this->assertInvoiceAdjustments($data->adjustments);
        $directLines = $this->assertInvoiceLines($data->directLines);
        $directLineOverrides = $this->indexDirectSourceLines($directLines);
        $consumedDirectOverrides = [];

        foreach ($data->sources as $source) {
            if (! $source instanceof SalesInvoiceSourceData) {
                throw new InvalidArgumentException('Sales invoice sources are invalid.');
            }

            if ($source->sourceType === 'sales_delivery') {
                $delivery = SalesDelivery::query()
                    ->with(['lines.salesOrderLine', 'adjustments'])
                    ->when($lockSources, fn ($query) => $query->lockForUpdate())
                    ->findOrFail($source->sourceId);
                $this->assertScope($delivery, $data);
                if (! in_array($delivery->status, [SalesDeliveryStatus::Posted, SalesDeliveryStatus::PartiallyInvoiced], true)) {
                    throw new InvalidArgumentException('Only posted sales deliveries can be invoiced.');
                }

                $resolvedCustomerId = $this->resolveCustomer($resolvedCustomerId, (int) $delivery->customer_id);
                $deliveries->push($delivery);
                $lineNumber = $this->appendDelivery(
                    $data,
                    $source,
                    $delivery,
                    $lineNumber,
                    $sources,
                    $sourceLines,
                    $invoiceLines,
                    $adjustments,
                    $sourceTotals,
                    $lineQuantities,
                    $directLineOverrides,
                    $consumedDirectOverrides,
                );

                continue;
            }

            if ($source->sourceType !== 'sales_order') {
                throw new InvalidArgumentException('Sales invoices require sales delivery or sales order sources.');
            }

            $order = SalesOrder::query()
                ->with(['lines', 'adjustments'])
                ->when($lockSources, fn ($query) => $query->lockForUpdate())
                ->findOrFail($source->sourceId);
            $this->assertScope($order, $data);
            $resolvedCustomerId = $this->resolveCustomer($resolvedCustomerId, (int) $order->customer_id);
            $lineNumber = $this->appendOrder(
                $data,
                $source,
                $order,
                $lineNumber,
                $sources,
                $sourceLines,
                $invoiceLines,
                $adjustments,
                $sourceTotals,
                $lineQuantities,
                $directLineOverrides,
                $consumedDirectOverrides,
            );
        }

        $lineNumber = $this->appendStandaloneDirectLines(
            $directLines,
            $lineNumber,
            $invoiceLines,
            $consumedDirectOverrides,
        );

        $unusedDirectSourceLines = array_diff_key($directLineOverrides, $consumedDirectOverrides);
        if ($unusedDirectSourceLines !== []) {
            throw new InvalidArgumentException('Sales invoice direct source lines must match the selected sales sources.');
        }

        if ($invoiceLines === []) {
            throw new InvalidArgumentException('No sales source quantities remain to invoice.');
        }

        return new PreparedSalesInvoiceData(
            invoiceData: new CreateInvoiceData(
                tenantId: $data->tenantId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: $data->invoiceDate,
                organizationUnitId: $data->organizationUnitId,
                invoiceNumber: $data->invoiceNumber,
                partyType: 'customer',
                partyId: $resolvedCustomerId,
                dueDate: $data->dueDate,
                currencyId: $data->currencyId,
                exchangeRate: $data->exchangeRate,
                status: $data->status,
                notes: $data->notes,
                createdBy: $data->createdBy,
                lines: $invoiceLines,
                sources: $sources,
                sourceLines: $sourceLines,
                adjustments: $adjustments,
            ),
            sourceTotals: $sourceTotals,
            lineQuantities: $lineQuantities,
            deliveries: $deliveries,
        );
    }

    private function appendDelivery(
        CreateSalesInvoiceData $data,
        SalesInvoiceSourceData $selection,
        SalesDelivery $delivery,
        int $lineNumber,
        array &$sources,
        array &$sourceLines,
        array &$invoiceLines,
        array &$invoiceAdjustments,
        array &$sourceTotals,
        array &$lineQuantities,
        array $directLineOverrides,
        array &$consumedDirectOverrides,
    ): int {
        $selectedTotal = '0.000000';
        $sourceSubtotal = '0.000000';
        $completeSelection = true;

        foreach ($delivery->lines as $line) {
            $sourceSubtotal = $this->math->add($sourceSubtotal, $this->math->mul((string) $line->delivered_quantity, (string) $line->unit_price));
            $remaining = $this->math->sub((string) $line->delivered_quantity, (string) $line->invoiced_quantity);
            $quantity = $this->math->normalize($selection->lineQuantities[(int) $line->getKey()] ?? $remaining);
            if ($this->math->isZero($quantity)) {
                if (! $this->math->isZero($remaining)) {
                    $completeSelection = false;
                }

                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException('Sales invoice quantity cannot exceed delivery remaining quantity.');
            }
            if ($this->math->compare($quantity, $remaining) < 0) {
                $completeSelection = false;
            }

            $lineTotal = $this->math->mul($quantity, (string) $line->unit_price);
            $selectedTotal = $this->math->add($selectedTotal, $lineTotal);
            $sourceLineKey = 'sales_delivery_line:'.$line->getKey();
            $lineQuantities[$sourceLineKey] = $quantity;
            $orderLine = $line->salesOrderLine;

            $invoiceLines[] = isset($directLineOverrides[$sourceLineKey])
                ? $this->overrideLine(
                    $directLineOverrides[$sourceLineKey],
                    $lineNumber++,
                    $quantity,
                    (int) $line->item_id,
                    $line->uom_id !== null ? (int) $line->uom_id : null,
                )
                : new InvoiceLineData(
                    lineNumber: $lineNumber++,
                    description: $line->description ?? 'Sales item',
                    quantity: $quantity,
                    unitPrice: (string) $line->unit_price,
                    lineType: InvoiceLineType::Item,
                    itemId: (int) $line->item_id,
                    uomId: $line->uom_id,
                    discountAmount: $orderLine instanceof SalesOrderLine ? $this->proportional((string) $orderLine->discount_amount, $quantity, (string) $orderLine->ordered_quantity) : '0.000000',
                    taxAmount: $orderLine instanceof SalesOrderLine ? $this->proportional((string) $orderLine->tax_amount, $quantity, (string) $orderLine->ordered_quantity) : '0.000000',
                    chargeAmount: $orderLine instanceof SalesOrderLine ? $this->proportional((string) $orderLine->charge_amount, $quantity, (string) $orderLine->ordered_quantity) : '0.000000',
                    sourceLineType: 'sales_delivery_line',
                    sourceLineId: (int) $line->getKey(),
                );

            if (isset($directLineOverrides[$sourceLineKey])) {
                $consumedDirectOverrides[$sourceLineKey] = true;
            }

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: 'sales_delivery',
                sourceId: (int) $delivery->getKey(),
                sourceLineType: 'sales_delivery_line',
                sourceLineId: (int) $line->getKey(),
                sourceQuantity: (string) $line->delivered_quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $line->unit_price,
                sourceLineTotal: $this->math->mul(
                    (string) $line->delivered_quantity,
                    (string) $line->unit_price,
                ),
                organizationUnitId: $data->organizationUnitId,
                previouslyInvoicedQuantity: (string) $line->invoiced_quantity,
            );
        }

        $adjustmentTotal = $this->appendAdjustments(
            $delivery->adjustments,
            'sales_delivery',
            (int) $delivery->getKey(),
            $invoiceAdjustments,
            $completeSelection,
        );
        $sourceTotals['sales_delivery:'.$delivery->getKey()] = ['line_total' => $selectedTotal, 'adjustment_total' => $adjustmentTotal];
        $sources[] = new InvoiceSourceData(
            tenantId: $data->tenantId,
            sourceType: 'sales_delivery',
            sourceId: (int) $delivery->getKey(),
            organizationUnitId: $data->organizationUnitId,
            sourceDocumentNumber: (string) $delivery->delivery_number,
            sourceDocumentDate: $delivery->delivery_date->toDateString(),
            sourceSubtotal: $sourceSubtotal,
            sourceAdjustmentTotal: $adjustmentTotal,
            sourceGrandTotal: $this->math->add($sourceSubtotal, $adjustmentTotal),
        );

        return $lineNumber;
    }

    private function appendOrder(
        CreateSalesInvoiceData $data,
        SalesInvoiceSourceData $selection,
        SalesOrder $order,
        int $lineNumber,
        array &$sources,
        array &$sourceLines,
        array &$invoiceLines,
        array &$invoiceAdjustments,
        array &$sourceTotals,
        array &$lineQuantities,
        array $directLineOverrides,
        array &$consumedDirectOverrides,
    ): int {
        $selectedTotal = '0.000000';
        $completeSelection = true;

        foreach ($order->lines as $line) {
            $basis = $this->math->compare((string) $line->delivered_quantity, '0.000000') > 0
                ? (string) $line->delivered_quantity
                : (string) $line->ordered_quantity;
            $remaining = $this->math->sub($basis, (string) $line->invoiced_quantity);
            $quantity = $this->math->normalize($selection->lineQuantities[(int) $line->getKey()] ?? $remaining);
            if ($this->math->isZero($quantity)) {
                if (! $this->math->isZero($remaining)) {
                    $completeSelection = false;
                }

                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException('Sales invoice quantity cannot exceed order remaining quantity.');
            }
            if ($this->math->compare($quantity, $remaining) < 0) {
                $completeSelection = false;
            }

            $selectedTotal = $this->math->add($selectedTotal, $this->math->mul($quantity, (string) $line->unit_price));
            $sourceLineKey = 'sales_order_line:'.$line->getKey();
            $lineQuantities[$sourceLineKey] = $quantity;

            $invoiceLines[] = isset($directLineOverrides[$sourceLineKey])
                ? $this->overrideLine(
                    $directLineOverrides[$sourceLineKey],
                    $lineNumber++,
                    $quantity,
                    (int) $line->item_id,
                    $line->ordered_uom_id !== null ? (int) $line->ordered_uom_id : null,
                )
                : new InvoiceLineData(
                    lineNumber: $lineNumber++,
                    description: $line->description ?? 'Sales item',
                    quantity: $quantity,
                    unitPrice: (string) $line->unit_price,
                    lineType: InvoiceLineType::Item,
                    itemId: (int) $line->item_id,
                    uomId: $line->ordered_uom_id,
                    discountAmount: $this->proportional((string) $line->discount_amount, $quantity, (string) $line->ordered_quantity),
                    taxAmount: $this->proportional((string) $line->tax_amount, $quantity, (string) $line->ordered_quantity),
                    chargeAmount: $this->proportional((string) $line->charge_amount, $quantity, (string) $line->ordered_quantity),
                    sourceLineType: 'sales_order_line',
                    sourceLineId: (int) $line->getKey(),
                );

            if (isset($directLineOverrides[$sourceLineKey])) {
                $consumedDirectOverrides[$sourceLineKey] = true;
            }

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: 'sales_order',
                sourceId: (int) $order->getKey(),
                sourceLineType: 'sales_order_line',
                sourceLineId: (int) $line->getKey(),
                sourceQuantity: (string) $line->ordered_quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $line->unit_price,
                sourceLineTotal: (string) $line->line_subtotal,
                organizationUnitId: $data->organizationUnitId,
                previouslyInvoicedQuantity: (string) $line->invoiced_quantity,
            );
        }

        $adjustmentTotal = $this->appendAdjustments(
            $order->adjustments,
            'sales_order',
            (int) $order->getKey(),
            $invoiceAdjustments,
            $completeSelection,
        );
        $sourceTotals['sales_order:'.$order->getKey()] = ['line_total' => $selectedTotal, 'adjustment_total' => $adjustmentTotal];
        $sources[] = new InvoiceSourceData(
            tenantId: $data->tenantId,
            sourceType: 'sales_order',
            sourceId: (int) $order->getKey(),
            organizationUnitId: $data->organizationUnitId,
            sourceDocumentNumber: (string) $order->sales_order_number,
            sourceDocumentDate: $order->sales_order_date->toDateString(),
            sourceSubtotal: (string) $order->subtotal,
            sourceAdjustmentTotal: $adjustmentTotal,
            sourceGrandTotal: (string) $order->grand_total,
        );

        return $lineNumber;
    }

    /**
     * @param  list<InvoiceLineData>  $directLines
     * @param  array<string, true>  $consumedDirectOverrides
     * @param  list<InvoiceLineData>  $invoiceLines
     */
    private function appendStandaloneDirectLines(
        array $directLines,
        int $lineNumber,
        array &$invoiceLines,
        array $consumedDirectOverrides,
    ): int {
        foreach ($directLines as $line) {
            $sourceLineKey = $line->sourceLineType !== null && $line->sourceLineId !== null
                ? $line->sourceLineType.':'.$line->sourceLineId
                : null;
            if ($sourceLineKey !== null && isset($consumedDirectOverrides[$sourceLineKey])) {
                continue;
            }
            if ($sourceLineKey !== null) {
                continue;
            }

            $invoiceLines[] = $this->cloneLine($line, $lineNumber++);
        }

        return $lineNumber;
    }

    /**
     * @param  list<InvoiceAdjustmentData>  $adjustments
     * @return list<InvoiceAdjustmentData>
     */
    private function assertInvoiceAdjustments(array $adjustments): array
    {
        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustmentData) {
                throw new InvalidArgumentException('Sales invoice adjustments are invalid.');
            }
        }

        return $adjustments;
    }

    /**
     * @param  list<InvoiceLineData>  $lines
     * @return list<InvoiceLineData>
     */
    private function assertInvoiceLines(array $lines): array
    {
        foreach ($lines as $line) {
            if (! $line instanceof InvoiceLineData) {
                throw new InvalidArgumentException('Sales invoice direct lines are invalid.');
            }
        }

        return $lines;
    }

    /**
     * @param  list<InvoiceLineData>  $lines
     * @return array<string, InvoiceLineData>
     */
    private function indexDirectSourceLines(array $lines): array
    {
        $indexed = [];

        foreach ($lines as $line) {
            if (($line->sourceLineType === null) !== ($line->sourceLineId === null)) {
                throw new InvalidArgumentException('Sales invoice direct source lines must provide source type and id together.');
            }
            if ($line->sourceLineType === null || $line->sourceLineId === null) {
                continue;
            }

            $key = $line->sourceLineType.':'.$line->sourceLineId;
            if (isset($indexed[$key])) {
                throw new InvalidArgumentException('Sales invoice direct source lines must be unique.');
            }
            $indexed[$key] = $line;
        }

        return $indexed;
    }

    private function overrideLine(
        InvoiceLineData $line,
        int $lineNumber,
        string $quantity,
        int $itemId,
        ?int $uomId,
    ): InvoiceLineData {
        if ($this->math->compare($line->quantity, $quantity) !== 0) {
            throw new InvalidArgumentException('Sales invoice direct source line quantity must match the selected source quantity.');
        }
        if ($line->itemId !== null && $line->itemId !== $itemId) {
            throw new InvalidArgumentException('Sales invoice direct source line item must match the selected source line item.');
        }
        if ($line->uomId !== null && $uomId !== null && $line->uomId !== $uomId) {
            throw new InvalidArgumentException('Sales invoice direct source line UOM must match the selected source line UOM.');
        }

        return $this->cloneLine($line, $lineNumber);
    }

    private function cloneLine(InvoiceLineData $line, int $lineNumber): InvoiceLineData
    {
        return new InvoiceLineData(
            lineNumber: $lineNumber,
            description: $line->description,
            quantity: $line->quantity,
            unitPrice: $line->unitPrice,
            lineType: $line->lineType,
            itemId: $line->itemId,
            uomId: $line->uomId,
            discountAmount: $line->discountAmount,
            taxAmount: $line->taxAmount,
            chargeAmount: $line->chargeAmount,
            lineTotal: $line->lineTotal,
            sourceLineType: $line->sourceLineType,
            sourceLineId: $line->sourceLineId,
            metadata: $line->metadata,
        );
    }

    private function appendAdjustments(
        Collection $adjustments,
        string $sourceType,
        int $sourceId,
        array &$target,
        bool $completeSelection,
    ): string {
        $total = '0.000000';
        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof SalesHeaderAdjustment || ! (bool) $adjustment->is_allocatable) {
                continue;
            }
            $total = $adjustment->effect->value === 'increase'
                ? $this->math->add($total, (string) $adjustment->amount)
                : $this->math->sub($total, (string) $adjustment->amount);
            $target[] = new InvoiceAdjustmentData(
                name: (string) $adjustment->name,
                adjustmentType: $this->invoiceAdjustmentType($adjustment),
                effect: AdjustmentEffect::from($adjustment->effect->value),
                amount: (string) $adjustment->amount,
                sourceAdjustmentType: 'sales_header_adjustment',
                sourceAdjustmentId: (int) $adjustment->getKey(),
                sourceType: $sourceType,
                sourceId: $sourceId,
                calculationType: $adjustment->calculation_type->value,
                rate: (string) $adjustment->rate,
                sourceAmount: (string) $adjustment->amount,
                allocationMethod: $completeSelection && $adjustment->allocation_method->value === 'proportional'
                    ? AllocationMethod::LastInvoice
                    : AllocationMethod::from($adjustment->allocation_method->value),
                isSystemGenerated: true,
                description: $adjustment->description,
            );
        }

        return $total;
    }

    private function invoiceAdjustmentType(SalesHeaderAdjustment $adjustment): AdjustmentType
    {
        return match ($adjustment->adjustment_type->value) {
            'discount' => AdjustmentType::Discount,
            'tax' => AdjustmentType::Tax,
            'freight' => AdjustmentType::Freight,
            'credit_note' => AdjustmentType::CreditNote,
            'debit_note' => AdjustmentType::DebitNote,
            'withholding' => AdjustmentType::Withholding,
            'rounding' => AdjustmentType::Rounding,
            'charge', 'insurance', 'service_charge', 'duty', 'levy' => AdjustmentType::Charge,
            default => AdjustmentType::Other,
        };
    }

    private function proportional(string $amount, string $quantity, string $sourceQuantity): string
    {
        return $this->math->isZero($amount) || $this->math->isZero($sourceQuantity)
            ? '0.000000'
            : $this->math->mul($amount, $this->math->div($quantity, $sourceQuantity, 12));
    }

    private function assertScope(object $source, CreateSalesInvoiceData $data): void
    {
        if ((int) $source->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Sales invoice source belongs to a different tenant.');
        }
        $sourceOrganizationUnitId = $source->organization_unit_id === null
            ? null
            : (int) $source->organization_unit_id;
        if ($sourceOrganizationUnitId !== $data->organizationUnitId) {
            throw new InvalidArgumentException('Sales invoice source belongs to a different organization unit.');
        }
    }

    private function resolveCustomer(?int $selected, int $source): int
    {
        if ($selected !== null && $selected !== $source) {
            throw new InvalidArgumentException('All sales invoice sources must belong to the selected customer.');
        }

        return $source;
    }
}
