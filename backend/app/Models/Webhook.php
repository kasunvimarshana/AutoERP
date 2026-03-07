<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook registration model.
 */
class Webhook extends Model
{
    protected $table = 'webhooks';

    protected $fillable = [
        'tenant_id',
        'url',
        'events',
        'secret',
        'is_active',
        'max_retries',
        'timeout',
        'custom_headers',
        'metadata',
        'last_triggered_at',
        'consecutive_failures',
    ];

    protected $hidden = ['secret'];

    protected $casts = [
        'events'               => 'array',
        'custom_headers'       => 'array',
        'metadata'             => 'array',
        'is_active'            => 'boolean',
        'max_retries'          => 'integer',
        'timeout'              => 'integer',
        'consecutive_failures' => 'integer',
        'last_triggered_at'    => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
