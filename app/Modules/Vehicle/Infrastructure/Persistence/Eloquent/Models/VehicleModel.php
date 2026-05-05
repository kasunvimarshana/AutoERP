<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleJobCardModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleRentalModel;

class VehicleModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'vehicles';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'customer_id',
        'supplier_id',
        'row_version',
        'ownership_type',
        'asset_code',
        'make',
        'model',
        'year',
        'vin',
        'registration_number',
        'chassis_number',
        'fuel_type',
        'transmission',
        'odometer',
        'rental_status',
        'service_status',
        'next_maintenance_due_at',
        'primary_image_path',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'customer_id' => 'integer',
        'supplier_id' => 'integer',
        'row_version' => 'integer',
        'year' => 'integer',
        'odometer' => 'decimal:6',
        'next_maintenance_due_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function jobCards(): HasMany
    {
        return $this->hasMany(VehicleJobCardModel::class, 'vehicle_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(VehicleRentalModel::class, 'vehicle_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocumentModel::class, 'vehicle_id');
    }
}
