<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Represents a domain event stored in the transactional outbox.
 *
 * The outbox processor reads pending rows and publishes them to the message
 * broker, giving at-least-once delivery guarantees even when the broker is
 * temporarily unavailable.
 *
 * @property int         $id
 * @property int|null    $tenant_id
 * @property string      $aggregate_type
 * @property int|null    $aggregate_id
 * @property string      $event_type
 * @property array       $payload
 * @property string      $status         pending|published|failed
 * @property int         $attempts
 * @property string|null $published_at
 * @property string|null $error_message
 */
class OutboxMessage extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'tenant_id',
        'aggregate_type',
        'aggregate_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'published_at',
        'error_message',
    ];

    protected $casts = [
        'payload'      => 'array',
        'attempts'     => 'integer',
        'published_at' => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function markPublished(): void
    {
        $this->update([
            'status'       => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'attempts'     => $this->attempts + 1,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => $error,
            'attempts'      => $this->attempts + 1,
        ]);
    }

    public function resetToPending(): void
    {
        $this->update([
            'status'        => self::STATUS_PENDING,
            'error_message' => null,
        ]);
    }
}
