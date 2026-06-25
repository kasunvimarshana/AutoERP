<?php

declare(strict_types=1);

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceSourceLine extends TenantOwnedModel
{
    protected $table = 'invoice_source_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_line_id' => 'integer',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'source_quantity' => 'decimal:6',
            'previously_invoiced_quantity' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'source_unit_price' => 'decimal:6',
            'source_line_total' => 'decimal:6',
            'invoiced_line_total' => 'decimal:6',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class, 'invoice_line_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
