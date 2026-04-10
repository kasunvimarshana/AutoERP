<?php

namespace App\Modules\Identity\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Role extends BaseModel
{
    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'guard',
        'is_system',
        'created_at'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
