<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

use Illuminate\Support\Collection;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Sales\Models\SalesDelivery;

final readonly class PreparedSalesInvoiceData
{
    /**
     * @param  array<string, array{line_total: string, adjustment_total: string}>  $sourceTotals
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, SalesDelivery>  $deliveries
     */
    public function __construct(
        public CreateInvoiceData $invoiceData,
        public array $sourceTotals,
        public array $lineQuantities,
        public Collection $deliveries,
    ) {}
}
