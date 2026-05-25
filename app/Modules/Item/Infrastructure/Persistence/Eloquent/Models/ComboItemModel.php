<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class ComboItemModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'combo_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'combo_item_id' => 'integer',
            'component_item_id' => 'integer',
            'component_variant_id' => 'integer',
            'sort_order' => 'integer',
            'quantity' => 'decimal:4',
            'uom_id' => 'integer',
            'standard_cost' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
            'incentive_value' => 'decimal:4',
        ]);
    }
}
