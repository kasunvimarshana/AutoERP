<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Permission extends BaseModel
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'module',
        'action',
        'description'
    ];

    protected $casts = [
        
    ];

    // Relationships are intentionally minimal here and can be extended in the domain layer.
}
