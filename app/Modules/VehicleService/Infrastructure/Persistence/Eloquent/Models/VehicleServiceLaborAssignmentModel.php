<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class VehicleServiceLaborAssignmentModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'vehicle_service_labor_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'hourly_rate' => 'decimal:4',
            'hours_worked' => 'decimal:4',
            'incentive_amount' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'job_card_id' => 'integer',
            'labor_item_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
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

    public function laborItem(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceLaborItemModel::class, 'labor_item_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }
}
