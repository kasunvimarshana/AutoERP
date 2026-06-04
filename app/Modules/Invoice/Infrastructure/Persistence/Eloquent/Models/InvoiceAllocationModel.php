<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceAllocationModel extends CoreModel
{
    protected $table = 'invoice_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'payment_id' => 'integer',
            'allocation_id' => 'integer',
            'allocated_amount' => 'decimal:4',
            'allocated_at' => 'datetime',
            'metadata_json' => 'array',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }
}
