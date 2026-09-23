<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

interface InvoicePaymentMethodProviderInterface
{
    /** @return list<string> */
    public function namesForInvoice(
        int $invoiceId,
        int $tenantId,
        ?int $organizationUnitId,
    ): array;
}
