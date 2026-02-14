<?php

namespace App\Modules\Customer\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *     title="Customer",
 *     description="Customer model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="code", type="string", example="CUST-001"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="mobile", type="string"),
 *     @OA\Property(property="address", type="string"),
 *     @OA\Property(property="city", type="string"),
 *     @OA\Property(property="state", type="string"),
 *     @OA\Property(property="country", type="string"),
 *     @OA\Property(property="postal_code", type="string"),
 *     @OA\Property(property="tax_id", type="string"),
 *     @OA\Property(property="customer_type", type="string", enum={"individual", "business"}),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class Customer extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'customer_type',
        'status',
        'notes',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Modules\Vehicle\Models\Vehicle::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Modules\Order\Models\Order::class, 'customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Invoice\Models\Invoice::class, 'customer_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isIndividual(): bool
    {
        return $this->customer_type === 'individual';
    }

    public function isBusiness(): bool
    {
        return $this->customer_type === 'business';
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

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('customer_type', $type);
    }
}
