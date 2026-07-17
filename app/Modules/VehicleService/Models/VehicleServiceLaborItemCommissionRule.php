<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final class VehicleServiceLaborItemCommissionRule extends TenantOwnedModel
{
    protected $table = 'vehicle_service_labor_item_commission_rules';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'row_version' => 'integer',
            'commission_type' => VehicleServiceCommissionType::class,
            'commission_value' => 'decimal:6',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
