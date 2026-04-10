<?php

namespace App\Modules\Traceability\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Identifier extends BaseModel
{
    protected $table = 'identifiers';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'identifier_type',
        'value',
        'gs1_application_identifiers',
        'epc_uri',
        'format',
        'is_primary',
        'is_active',
        'metadata',
        'created_at'
    ];

    protected $casts = [
        'gs1_application_identifiers' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }
}
