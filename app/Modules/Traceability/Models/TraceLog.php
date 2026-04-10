<?php

namespace App\Modules\Traceability\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TraceLog extends BaseModel
{
    protected $table = 'trace_logs';

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'identifier_id',
        'action_type',
        'reference_type',
        'reference_id',
        'source_location_id',
        'destination_location_id',
        'quantity',
        'user_id',
        'device_id',
        'metadata',
        'timestamp'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'metadata' => 'array',
        'timestamp' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function identifier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Traceability\Models\Identifier::class, 'identifier_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class, 'destination_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'user_id');
    }
}
