<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class VehicleServiceDiagnosticModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope, SoftDeletes;

    protected $table = 'vehicle_service_diagnostics';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'job_card_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'performed_at' => 'datetime',
            'performed_by' => 'integer',
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

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'performed_by');
    }

    public function vehicleServiceDiagnosticLines(): HasMany
    {
        return $this->hasMany(VehicleServiceDiagnosticLineModel::class, 'diagnostic_id');
    }
}

