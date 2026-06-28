<?php

declare(strict_types=1);

namespace Modules\Purchase\Services\Tax;

use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Tax\Data\TaxableDocumentData;
use Modules\Tax\Data\TaxableDocumentLineData;

final class GoodsReceiptNoteTaxDocumentMapper
{
    public function map(GoodsReceiptNote $goodsReceipt): TaxableDocumentData
    {
        $goodsReceipt->loadMissing('lines');
        $lines = [];
        foreach ($goodsReceipt->lines as $line) {
            $lines[] = new TaxableDocumentLineData(
                lineId: (int) $line->getKey(),
                lineNumber: count($lines) + 1,
                quantity: (string) $line->accepted_quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id === null ? null : (int) $line->item_id,
                taxGroupId: $line->tax_group_id === null ? null : (int) $line->tax_group_id,
                discountBeforeTax: (string) $line->discount_amount,
                chargeAfterTax: (string) $line->charge_amount,
            );
        }

        return new TaxableDocumentData(
            tenantId: (int) $goodsReceipt->tenant_id,
            organizationUnitId: $goodsReceipt->organization_unit_id === null ? null : (int) $goodsReceipt->organization_unit_id,
            documentType: 'purchase_goods_receipt_note',
            sourceModule: 'purchase',
            sourceType: 'goods_receipt_note',
            sourceId: (int) $goodsReceipt->getKey(),
            sourceNumber: (string) $goodsReceipt->grn_number,
            sourceDate: $goodsReceipt->received_date->toDateString(),
            transactionDate: $goodsReceipt->posted_at?->toDateString() ?? $goodsReceipt->received_date->toDateString(),
            partyType: $goodsReceipt->supplier_id === null ? null : 'supplier',
            partyId: $goodsReceipt->supplier_id === null ? null : (int) $goodsReceipt->supplier_id,
            lines: $lines,
        );
    }
}
