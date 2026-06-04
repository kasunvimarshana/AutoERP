<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceLineModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'invoice_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_id' => 'integer',
            'line_no' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_total' => 'decimal:4',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'source_context' => 'array',
            'schema_version' => 'integer',
            'data_json' => 'array',
            'metadata_json' => 'array',
        ]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(InvoiceLineSourceModel::class, 'invoice_line_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceLineTaxModel::class, 'invoice_line_id');
    }
}
