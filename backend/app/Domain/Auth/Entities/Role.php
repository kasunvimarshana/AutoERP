<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Domain entity for a Role, extending Spatie's base Role model.
 */
class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
