<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceLineSourceModel extends CoreModel
{
    protected $table = 'invoice_line_sources';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_line_id' => 'integer',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'source_context' => 'array',
            'quantity_billed' => 'decimal:4',
            'amount_billed' => 'decimal:4',
            'metadata_json' => 'array',
        ]);
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLineModel::class, 'invoice_line_id');
    }
}
