<?php

namespace App\Modules\Config\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Setting extends BaseModel
{
    protected $table = 'settings';

    protected $fillable = [
        'tenant_id',
        'module',
        'key',
        'value',
        'type',
        'updated_at'
    ];

    protected $casts = [
        'value' => 'array',
        'updated_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
