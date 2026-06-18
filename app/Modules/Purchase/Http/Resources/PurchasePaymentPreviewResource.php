<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Modules\Purchase\DTOs\PurchasePaymentPreviewData;

final class PurchasePaymentPreviewResource extends PurchaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PurchasePaymentPreviewData $preview */
        $preview = $this->resource;

        return [
            'tenant_id' => $preview->tenantId,
            'organization_unit_id' => $preview->organizationUnitId,
            'payment_date' => $preview->paymentDate,
            'amount' => $preview->amount,
            'line_total' => $preview->lineTotal,
            'allocation_total' => $preview->allocationTotal,
            'unapplied_amount' => $preview->unappliedAmount,
            'supplier_type' => $preview->supplierType,
            'supplier_id' => $preview->supplierId,
            'currency_id' => $preview->currencyId,
            'exchange_rate' => $preview->exchangeRate,
            'reference_number' => $preview->referenceNumber,
            'lines' => $preview->lines,
            'allocations' => $preview->allocations,
        ];
    }
}
