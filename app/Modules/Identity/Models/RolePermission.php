<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RolePermission extends BaseModel
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id'
    ];

    protected $casts = [
        
    ];

    // Relationships are intentionally minimal here and can be extended in the domain layer.
}
