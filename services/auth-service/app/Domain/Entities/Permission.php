<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Entity
 *
 * Represents an ABAC permission in the system.
 */
class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = ['name', 'description', 'resource', 'action'];

    /**
     * Get roles with this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
