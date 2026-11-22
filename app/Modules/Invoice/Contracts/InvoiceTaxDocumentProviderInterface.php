<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Contracts;

use Modules\Tax\Data\TaxableDocumentData;

interface InvoiceTaxDocumentProviderInterface
{
    public function taxableDocument(int $tenantId, ?int $organizationUnitId, int $invoiceId): TaxableDocumentData;
}
