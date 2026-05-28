<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class GrnHeaderModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'grn_headers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'purchase_order_id' => 'integer',
            'inspection_status' => 'string',
            'putaway_status' => 'string',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:4',
            'received_date' => 'date',
            'price_list_id' => 'integer',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_group_id' => 'integer',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'submitted_by' => 'integer',
            'confirmed_by' => 'integer',
            'posted_by' => 'integer',
            'cancelled_by' => 'integer',
            'reversed_by' => 'integer',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'posted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ]);
    }
}
