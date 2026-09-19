<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\User\Models\UserModel;
use Modules\VehicleService\Enums\VehicleServiceDiscountCalculationType;
use Modules\VehicleService\Enums\VehicleServiceDiscountRevisionAction;

final class VehicleServiceJobDiscount extends TenantOwnedModel
{
    public $timestamps = false;

    protected $table = 'vehicle_service_job_discounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'revision' => 'integer',
            'action' => VehicleServiceDiscountRevisionAction::class,
            'calculation_type' => VehicleServiceDiscountCalculationType::class,
            'rate' => 'decimal:6',
            'fixed_amount' => 'decimal:6',
            'calculation_base_snapshot' => 'decimal:6',
            'calculated_amount_snapshot' => 'decimal:6',
            'changed_by' => 'integer',
            'changed_at' => 'datetime',
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'changed_by');
    }
}
