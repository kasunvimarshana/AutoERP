<?php

namespace Enterprise\Core\Messaging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * OutboxPattern - Ensures reliable event publishing.
 * Events are first saved in the local database in the same transaction as the domain change,
 * then published to the message broker (Kafka/RabbitMQ) by a separate worker.
 */
class OutboxEvent extends Model
{
    protected $guarded = [];
    protected $table = 'outbox_events';

    public $timestamps = false;
    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Dispatch an event within a database transaction.
     */
    public static function dispatch(string $eventType, array $payload, array $metadata = [])
    {
        return self::create([
            'event_type' => $eventType,
            'payload' => $payload,
            'metadata' => array_merge($metadata, [
                'dispatched_at' => now()->toIso8601String(),
                'source_service' => config('app.name'),
            ]),
            'status' => 'PENDING',
            'created_at' => now(),
        ]);
    }
}
