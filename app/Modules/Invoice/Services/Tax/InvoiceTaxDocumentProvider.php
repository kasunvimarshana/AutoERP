<?php

declare(strict_types=1);

namespace Modules\Invoice\Services\Tax;

use Modules\Invoice\Contracts\InvoiceTaxDocumentProviderInterface;
use Modules\Invoice\Models\Invoice;
use Modules\Tax\Data\TaxableDocumentData;

final class InvoiceTaxDocumentProvider implements InvoiceTaxDocumentProviderInterface
{
    public function __construct(private readonly InvoiceTaxDocumentMapper $mapper) {}

    public function taxableDocument(int $tenantId, ?int $organizationUnitId, int $invoiceId): TaxableDocumentData
    {
        $invoice = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->findOrFail($invoiceId);

        return $this->mapper->map($invoice);
    }
}
