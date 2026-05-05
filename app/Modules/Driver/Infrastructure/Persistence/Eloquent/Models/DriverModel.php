<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DriverModel extends Model
{
    protected $table = 'drivers';
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'address',
        'id_number',
        'is_available',
        'hire_date',
        'termination_date',
        'tenant_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'date_of_birth' => 'date',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(LicenseModel::class, 'driver_id', 'id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(DriverAvailabilityModel::class, 'driver_id', 'id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(DriverCommissionModel::class, 'driver_id', 'id');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_available', true)->whereNull('termination_date');
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}",
        );
    }
}
