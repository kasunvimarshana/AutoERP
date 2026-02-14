<?php

namespace App\Modules\Permission\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     description="Permission model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="users.view"),
 *     @OA\Property(property="guard_name", type="string", example="web"),
 *     @OA\Property(property="module", type="string", example="users"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'module',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
