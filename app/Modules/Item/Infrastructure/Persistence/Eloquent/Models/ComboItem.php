<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ComboItem extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'combo_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'standard_cost' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function comboItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'combo_item_id');
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'component_variant_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'uom_id'
        );
    }
}
