<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class VehicleServiceLaborItemModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'vehicle_service_labor_items';

    protected $guarded = ['id', 'gross_amount', 'line_total', 'line_total_with_tax'];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'combo_item_id' => 'integer',
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'incentive_amount' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'item_id' => 'integer',
            'job_card_id' => 'integer',
            'line_total' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'tax_group_id' => 'integer',
            'tenant_id' => 'integer',
            'unit_cost' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'uom_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJobCardModel::class, 'job_card_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function comboItem(): BelongsTo
    {
        return $this->belongsTo(ComboItemModel::class, 'combo_item_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function vehicleServiceLaborAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborAssignmentModel::class, 'labor_item_id');
    }
}
