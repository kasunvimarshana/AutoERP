<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Invoice\Models\Invoice;

final class SalesInvoiceLink extends TenantOwnedModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'source_line_total' => 'decimal:6',
            'allocated_adjustment_total' => 'decimal:6',
            'invoice_total' => 'decimal:6',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
