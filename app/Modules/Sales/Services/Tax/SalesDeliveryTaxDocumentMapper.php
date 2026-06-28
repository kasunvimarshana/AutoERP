<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Tax;

use Modules\Sales\Models\SalesDelivery;
use Modules\Tax\Data\TaxableDocumentData;
use Modules\Tax\Data\TaxableDocumentLineData;

final class SalesDeliveryTaxDocumentMapper
{
    public function map(SalesDelivery $delivery): TaxableDocumentData
    {
        $delivery->loadMissing(['lines.salesOrderLine']);
        $lines = [];
        foreach ($delivery->lines as $line) {
            $lines[] = new TaxableDocumentLineData(
                lineId: (int) $line->getKey(),
                lineNumber: count($lines) + 1,
                quantity: (string) $line->delivered_quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id === null ? null : (int) $line->item_id,
                discountBeforeTax: (string) ($line->salesOrderLine?->discount_amount ?? '0.000000'),
                chargeAfterTax: (string) ($line->salesOrderLine?->charge_amount ?? '0.000000'),
            );
        }

        return new TaxableDocumentData(
            tenantId: (int) $delivery->tenant_id,
            organizationUnitId: $delivery->organization_unit_id === null ? null : (int) $delivery->organization_unit_id,
            documentType: 'sales_delivery',
            sourceModule: 'sales',
            sourceType: 'sales_delivery',
            sourceId: (int) $delivery->getKey(),
            sourceNumber: (string) $delivery->delivery_number,
            sourceDate: $delivery->delivery_date->toDateString(),
            transactionDate: $delivery->posted_at?->toDateString() ?? $delivery->delivery_date->toDateString(),
            partyType: $delivery->customer_id === null ? null : 'customer',
            partyId: $delivery->customer_id === null ? null : (int) $delivery->customer_id,
            lines: $lines,
        );
    }
}
