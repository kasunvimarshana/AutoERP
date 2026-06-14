<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceLine;
use Modules\VehicleRental\Models\Concerns\ScopesRentalContext;

final class RentalInvoiceLink extends CoreModel
{
    use ScopesRentalContext;

    protected $table = 'rental_invoice_links';

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'charge_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_line_id' => 'integer',
            'invoiced_quantity' => 'decimal:6',
            'invoiced_amount' => 'decimal:6',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(RentalCharge::class, 'charge_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class, 'invoice_line_id');
    }
}
