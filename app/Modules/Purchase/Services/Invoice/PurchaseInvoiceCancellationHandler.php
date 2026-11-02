<?php

declare(strict_types=1);

namespace Modules\Purchase\Services\Invoice;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceCancellationContext;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Services\PurchaseAdjustmentAllocationService;
use Modules\Purchase\Services\PurchaseInvoiceQuantityUpdater;

final class PurchaseInvoiceCancellationHandler implements InvoiceSourceCancellationHandlerInterface
{
    private const SUPPORTED_LINE_TYPES = [
        'goods_receipt_note_line',
        'purchase_order_line',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseInvoiceQuantityUpdater $quantities,
        private readonly PurchaseAdjustmentAllocationService $adjustmentAllocations,
    ) {}

    public function supports(InvoiceSourceCancellationContext $context): bool
    {
        return PurchaseInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->exists();
    }

    public function restore(InvoiceSourceCancellationContext $context): void
    {
        $lineQuantities = [];
        $goodsReceiptIds = [];
        foreach ($context->sourceLines as $sourceLine) {
            if (! in_array($sourceLine->sourceLineType, self::SUPPORTED_LINE_TYPES, true)) {
                continue;
            }

            $lineKey = $sourceLine->sourceLineType.':'.$sourceLine->sourceLineId;
            $lineQuantities[$lineKey] = $this->math->add(
                $lineQuantities[$lineKey] ?? '0.000000',
                $sourceLine->invoicedQuantity,
            );
            if ($sourceLine->sourceType === 'goods_receipt_note') {
                $goodsReceiptIds[] = $sourceLine->sourceId;
            }
        }

        if ($lineQuantities !== []) {
            $this->quantities->reverse($lineQuantities, $this->goodsReceipts($context, $goodsReceiptIds));
        }

        $this->adjustmentAllocations->releaseForTarget(
            'purchase_invoice',
            $context->invoiceId,
            'purchase_invoice_cancel',
        );

        PurchaseInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }

    /**
     * @param list<int> $ids
     * @return Collection<int, GoodsReceiptNote>
     */
    private function goodsReceipts(InvoiceSourceCancellationContext $context, array $ids): Collection
    {
        return $this->scope(GoodsReceiptNote::query(), $context)
            ->whereIn('id', array_values(array_unique($ids)))
            ->lockForUpdate()
            ->get();
    }

    private function scope(Builder $query, InvoiceSourceCancellationContext $context): Builder
    {
        $query->where('tenant_id', $context->tenantId);

        return $context->organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $context->organizationUnitId);
    }
}
