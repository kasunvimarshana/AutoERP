<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;

class ComboItemModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, SoftDeletes;

    protected $table = 'combo_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
            'sales_price' => 'decimal:4',
            'sort_order' => 'integer',
            'standard_cost' => 'decimal:4',
        ];
    }

    public function comboItem(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'combo_item_id');
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'component_item_id');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ItemVariantModel::class, 'component_variant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function vehicleServiceLaborItems(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborItemModel::class, 'combo_item_id');
    }

}
