<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Vehicle\Domain\Enums\VehicleStatus;
use Modules\Vehicle\Domain\Enums\VehicleUsageProfile;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Vehicle extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'year' => 'integer',
            'usage_profile' => VehicleUsageProfile::class,
            'seating_capacity' => 'integer',
            'current_odometer' => 'integer',
            'status' => VehicleStatus::class,
            'registration_expiry' => 'date',
            'insurance_expiry' => 'date',
            'last_service_date' => 'date',
            'last_service_odometer' => 'integer',
            'next_service_due_date' => 'date',
            'next_service_due_odometer' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', VehicleStatus::Active->value);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            'Modules\\Vehicle\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleDocument',
            'vehicle_id'
        );
    }

    public function customerVehicles(): HasMany
    {
        return $this->hasMany(
            'Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Models\\CustomerVehicle',
            'vehicle_id'
        );
    }
}
