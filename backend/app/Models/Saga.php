<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saga execution log model — persists the state of each distributed transaction.
 */
class Saga extends Model
{
    protected $table = 'sagas';

    protected $fillable = [
        'saga_id',
        'type',
        'status',
        'context',
        'steps',
        'error_message',
        'tenant_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context'      => 'array',
        'steps'        => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
