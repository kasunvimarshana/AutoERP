<?php

declare(strict_types=1);

namespace Modules\Item\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tax\Models\TaxGroup;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class Item extends CoreModel
{
    use SoftDeletes;

    protected $table = 'items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_category_id' => 'integer',
            'item_brand_id' => 'integer',
            'base_uom_id' => 'integer',
            'default_tax_group_id' => 'integer',
            'purchase_tax_group_id' => 'integer',
            'sales_tax_group_id' => 'integer',
            'item_type' => ItemType::class,
            'tracking_type' => TrackingType::class,
            'costing_method' => CostingMethod::class,
            'is_stockable' => 'boolean',
            'is_combo' => 'boolean',
            'is_tax_exempt' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ItemBrand::class, 'item_brand_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function defaultTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'default_tax_group_id');
    }

    public function purchaseTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'purchase_tax_group_id');
    }

    public function salesTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroup::class, 'sales_tax_group_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class, 'item_id');
    }

    public function baseUomRevisions(): HasMany
    {
        return $this->hasMany(ItemBaseUomRevision::class, 'item_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ItemVariant::class, 'item_id');
    }

    public function bundleLines(): HasMany
    {
        return $this->hasMany(ItemBundle::class, 'parent_item_id');
    }

    public function usedInBundles(): HasMany
    {
        return $this->hasMany(ItemBundle::class, 'child_item_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ItemPrice::class, 'item_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ItemCode::class, 'item_id');
    }

    public function usageRules(): HasMany
    {
        return $this->hasMany(ItemUsageRule::class, 'item_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query
            : $query->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
    }
}
