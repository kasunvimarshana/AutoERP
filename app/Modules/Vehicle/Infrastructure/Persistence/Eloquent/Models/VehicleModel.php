<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

final class VehicleModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'year' => 'integer',
            'service_enabled' => 'boolean',
            'rental_enabled' => 'boolean',
            'seating_capacity' => 'integer',
            'current_odometer' => 'integer',
            'last_service_odometer' => 'integer',
            'next_service_due_odometer' => 'integer',
            'registration_expiry' => 'date',
            'insurance_expiry' => 'date',
            'last_service_date' => 'date',
            'next_service_due_date' => 'date',
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
}
