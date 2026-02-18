<?php

namespace App\Modules\IAM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Model
 *
 * Represents a permission in the RBAC system.
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\IAM\PermissionFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];

    /**
     * The roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Grant this permission to a role
     */
    public function assignToRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Revoke this permission from a role
     */
    public function removeFromRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }
}
