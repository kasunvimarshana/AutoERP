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

use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLayout;

interface InvoiceLayoutResolverInterface
{
    public const DEFAULT_LAYOUT_KEY = 'invoice.default_layout';
    public const PRINTER_LAYOUTS_KEY = 'invoice.printer_layouts';

    public function resolve(Invoice $invoice, ?string $explicitLayoutCode = null, ?string $printer = null): InvoiceLayout;

    public function resolveForScope(int $tenantId, ?int $organizationUnitId = null, ?string $printer = null): InvoiceLayout;
}
