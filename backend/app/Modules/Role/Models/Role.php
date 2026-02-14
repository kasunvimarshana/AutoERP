<?php

namespace App\Modules\Role\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="Role model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="admin"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Permission")
 *     )
 * )
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'tenant_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'model_has_roles',
            'role_id',
            'model_id'
        )->where('model_type', User::class);
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
