<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Tax\Data\TaxableDocumentData;

interface InvoiceTaxDocumentProviderInterface
{
    public function taxableDocument(int $tenantId, ?int $organizationUnitId, int $invoiceId): TaxableDocumentData;
}
