<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseReturnLineModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_return_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'purchase_return_id' => 'integer',
            'original_grn_line_id' => 'integer',
            'original_purchase_order_line_id' => 'integer',
            'original_document_line_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'batch_id' => 'integer',
            'serial_id' => 'integer',
            'warehouse_id' => 'integer',
            'location_id' => 'integer',
            'uom_id' => 'integer',
            'return_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'restocking_fee' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'account_id' => 'integer'
        ]);
    }
}
