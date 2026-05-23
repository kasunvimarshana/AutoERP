<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class VehicleServiceInspectionLineModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'vehicle_service_inspection_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'inspection_id' => 'integer',
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

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceInspectionModel::class, 'inspection_id');
    }
}
