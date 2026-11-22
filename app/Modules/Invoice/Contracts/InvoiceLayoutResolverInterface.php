<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLayout;

interface InvoiceLayoutResolverInterface
{
    public const DEFAULT_LAYOUT_KEY = 'invoice.default_layout';

    public function resolve(Invoice $invoice, ?string $explicitLayoutCode = null): InvoiceLayout;

    public function resolveForScope(int $tenantId, ?int $organizationUnitId = null): InvoiceLayout;
}
