<?php

namespace App\Modules\Core\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Tenant extends BaseModel
{
    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'domain',
        'plan',
        'status',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    // Relationships are intentionally minimal here and can be extended in the domain layer.
}
