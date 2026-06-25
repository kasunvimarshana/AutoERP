<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;

final class PurchaseInvoiceLink extends TenantOwnedModel
{
    protected $table = 'purchase_invoice_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'source_id' => 'integer',
            'source_line_total' => 'decimal:6',
            'allocated_adjustment_total' => 'decimal:6',
            'invoice_total' => 'decimal:6',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
