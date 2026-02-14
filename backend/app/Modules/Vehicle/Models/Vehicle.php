<?php

namespace App\Modules\Vehicle\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Vehicle",
 *     type="object",
 *     title="Vehicle",
 *     description="Vehicle model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="customer_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186"),
 *     @OA\Property(property="registration_number", type="string", example="ABC-1234"),
 *     @OA\Property(property="make", type="string", example="Toyota"),
 *     @OA\Property(property="model", type="string", example="Camry"),
 *     @OA\Property(property="year", type="integer", example=2024),
 *     @OA\Property(property="color", type="string", example="Blue"),
 *     @OA\Property(property="fuel_type", type="string", example="Petrol"),
 *     @OA\Property(property="transmission_type", type="string", example="Automatic"),
 *     @OA\Property(property="engine_number", type="string"),
 *     @OA\Property(property="chassis_number", type="string"),
 *     @OA\Property(property="mileage", type="integer", example=50000),
 *     @OA\Property(property="purchase_date", type="string", format="date"),
 *     @OA\Property(property="last_service_date", type="string", format="date"),
 *     @OA\Property(property="next_service_date", type="string", format="date"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Vehicle extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'branch_id',
        'vin',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'fuel_type',
        'transmission_type',
        'engine_number',
        'chassis_number',
        'mileage',
        'purchase_date',
        'last_service_date',
        'next_service_date',
        'status',
        'notes',
        'settings',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'purchase_date' => 'date',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function serviceHistory(): HasMany
    {
        return $this->hasMany(\App\Modules\Service\Models\ServiceRecord::class, 'vehicle_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isDueForService(): bool
    {
        if (!$this->next_service_date) {
            return false;
        }
        
        return $this->next_service_date->isPast() || $this->next_service_date->isToday();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeDueForService($query)
    {
        return $query->whereNotNull('next_service_date')
            ->whereDate('next_service_date', '<=', now()->addDays(7));
    }
}
