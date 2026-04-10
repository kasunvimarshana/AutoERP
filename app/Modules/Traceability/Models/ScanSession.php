<?php

namespace App\Modules\Traceability\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScanSession extends BaseModel
{
    protected $table = 'scan_sessions';

    protected $fillable = [
        'tenant_id',
        'session_type',
        'reference_type',
        'reference_id',
        'user_id',
        'device_info',
        'started_at',
        'completed_at',
        'status'
    ];

    protected $casts = [
        'device_info' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'user_id');
    }
}
