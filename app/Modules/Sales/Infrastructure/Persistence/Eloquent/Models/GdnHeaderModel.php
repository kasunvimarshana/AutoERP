<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class GdnHeaderModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'gdn_headers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'customer_id' => 'integer',
            'warehouse_id' => 'integer',
            'sales_order_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:6',
            'delivered_date' => 'date',
            'price_list_id' => 'integer',
            'expected_qty_total' => 'decimal:4',
            'picked_qty_total' => 'decimal:4',
            'delivered_qty_total' => 'decimal:4',
            'short_qty_total' => 'decimal:4',
            'rejected_qty_total' => 'decimal:4',
            'returned_qty_total' => 'decimal:4',
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
            'tax_account_id' => 'integer',
            'discount_account_id' => 'integer',
            'sales_account_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'submitted_by' => 'integer',
            'submitted_at' => 'datetime',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'confirmed_by' => 'integer',
            'confirmed_at' => 'datetime',
            'posted_by' => 'integer',
            'posted_at' => 'datetime',
            'cancelled_by' => 'integer',
            'cancelled_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }
}
