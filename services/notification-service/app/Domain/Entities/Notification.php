<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Notification Entity
 *
 * Tracks sent notifications for audit and replay.
 */
class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',      // email | webhook | sms | push
        'channel',   // specific channel identifier
        'event',     // order.created | stock.low | etc.
        'status',    // pending | sent | failed
        'payload',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];
}
