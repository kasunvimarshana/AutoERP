<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

final readonly class PurchaseInvoicePosted
{
    public function __construct(public int $invoiceId)
    {
    }
}
