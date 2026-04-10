<?php

namespace App\Modules\Core\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Organization extends BaseModel
{
    protected $table = 'organizations';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'code',
        'type',
        'level',
        'path',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
