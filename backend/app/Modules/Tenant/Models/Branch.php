<?php

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class Branch extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'organization_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'is_warehouse',
        'is_store',
        'status',
        'settings',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_warehouse' => 'boolean',
        'is_store' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the branch.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the organization that owns the branch.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the locations for the branch.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the users in the branch.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the user who created the branch.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the branch.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if branch is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
