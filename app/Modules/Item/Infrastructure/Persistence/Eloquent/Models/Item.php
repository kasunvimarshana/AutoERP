<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Domain\Enums\ItemStatus;
use Modules\Item\Domain\Enums\ItemType;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Item extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => ItemType::class,
            'status' => ItemStatus::class,
            'is_batch_tracked' => 'boolean',
            'is_lot_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
            'is_stockable' => 'boolean',
            'standard_cost' => 'decimal:4',
            'is_active' => 'boolean',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
            'estimated_service_time_hours' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_quantity' => 'decimal:4',
            'safety_stock' => 'decimal:4',
            'lead_time_days' => 'integer',
            'review_period_days' => 'integer',
            'auto_replenishment_enabled' => 'boolean',
            'allow_auto_purchase_order' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function stockable(Builder $query): void
    {
        $query->where('is_stockable', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ItemBrand::class, 'brand_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'base_uom_id'
        );
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'purchase_uom_id'
        );
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'sales_uom_id'
        );
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'tax_group_id'
        );
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ItemVariant::class, 'item_id');
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(ItemVariantAttribute::class, 'item_id');
    }

    public function comboComponents(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'combo_item_id');
    }

    public function componentOfCombos(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'component_item_id');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(ItemIdentifier::class, 'item_id');
    }
}
