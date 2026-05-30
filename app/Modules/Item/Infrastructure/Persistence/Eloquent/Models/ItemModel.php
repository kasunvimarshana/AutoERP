<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ItemModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'item_type_id' => 'integer',
            'base_uom_id' => 'integer',
            'default_receipt_uom_id' => 'integer',
            'default_issue_uom_id' => 'integer',
            'default_consumption_uom_id' => 'integer',
            'default_charge_uom_id' => 'integer',
            'tax_group_id' => 'integer',
            'default_currency_id' => 'integer',
            'is_batch_tracked' => 'boolean',
            'is_lot_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
            'is_stockable' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_service' => 'boolean',
            'is_rentable' => 'boolean',
            'is_chargeable' => 'boolean',
            'is_taxable' => 'boolean',
            'is_variable' => 'boolean',
            'standard_cost' => 'decimal:4',
            'income_account_id' => 'integer',
            'cogs_account_id' => 'integer',
            'inventory_account_id' => 'integer',
            'expense_account_id' => 'integer',
            'return_in_account_id' => 'integer',
            'return_out_account_id' => 'integer',
            'inventory_gain_account_id' => 'integer',
            'inventory_loss_account_id' => 'integer',
            'stock_transfer_account_id' => 'integer',
            'wip_account_id' => 'integer',
            'price_variance_account_id' => 'integer',
            'is_active' => 'boolean',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'safety_stock' => 'decimal:4',
            'lead_time_days' => 'integer',
            'review_period_days' => 'integer',
            'auto_replenishment_enabled' => 'boolean',
        ]);
    }
}
