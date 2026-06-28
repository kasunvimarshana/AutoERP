<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Invoice;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceCancellationContext;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesInvoiceLink;
use Modules\Sales\Services\SalesInvoiceQuantityUpdater;

final class SalesInvoiceCancellationHandler implements InvoiceSourceCancellationHandlerInterface
{
    private const SUPPORTED_LINE_TYPES = [
        'sales_delivery_line',
        'sales_order_line',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesInvoiceQuantityUpdater $quantities,
    ) {}

    public function supports(InvoiceSourceCancellationContext $context): bool
    {
        return SalesInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->exists();
    }

    public function restore(InvoiceSourceCancellationContext $context): void
    {
        $lineQuantities = [];
        $deliveryIds = [];
        foreach ($context->sourceLines as $sourceLine) {
            if (! in_array($sourceLine->sourceLineType, self::SUPPORTED_LINE_TYPES, true)) {
                continue;
            }

            $lineKey = $sourceLine->sourceLineType.':'.$sourceLine->sourceLineId;
            $lineQuantities[$lineKey] = $this->math->add(
                $lineQuantities[$lineKey] ?? '0.000000',
                $sourceLine->invoicedQuantity,
            );
            if ($sourceLine->sourceType === 'sales_delivery') {
                $deliveryIds[] = $sourceLine->sourceId;
            }
        }

        if ($lineQuantities !== []) {
            $this->quantities->reverse($lineQuantities, $this->deliveries($context, $deliveryIds));
        }

        SalesInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }

    /**
     * @param list<int> $ids
     * @return Collection<int, SalesDelivery>
     */
    private function deliveries(InvoiceSourceCancellationContext $context, array $ids): Collection
    {
        return $this->scope(SalesDelivery::query(), $context)
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
