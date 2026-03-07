<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'failure_count',
        'last_triggered_at',
        'metadata',
    ];

    protected $casts = [
        'events'            => 'array',
        'metadata'          => 'array',
        'is_active'         => 'boolean',
        'failure_count'     => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = ['secret'];
}
