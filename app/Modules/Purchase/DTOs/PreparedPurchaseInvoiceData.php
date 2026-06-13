<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

use Illuminate\Support\Collection;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Purchase\Models\GoodsReceiptNote;

final readonly class PreparedPurchaseInvoiceData
{
    /**
     * @param  array<string, array{line_total: string, adjustment_total: string}>  $sourceTotals
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, GoodsReceiptNote>  $goodsReceipts
     */
    public function __construct(
        public CreateInvoiceData $invoiceData,
        public array $sourceTotals,
        public array $lineQuantities,
        public Collection $goodsReceipts,
    ) {}
}
