<?php

declare(strict_types=1);

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OutboxEvent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'outbox_events';

    protected $fillable = [
        'tenant_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'status',
        'retry_count',
        'published_at',
        'failed_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
