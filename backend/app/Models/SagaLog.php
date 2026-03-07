<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Persists the execution state of a saga run for observability and recovery.
 *
 * @property int         $id
 * @property string      $saga_id
 * @property string      $saga_type
 * @property string      $status       started|completed|failed|compensating|compensated
 * @property string|null $current_step
 * @property array|null  $context
 * @property array|null  $compensation_log
 * @property string|null $error_message
 * @property string|null $completed_at
 */
class SagaLog extends Model
{
    public const STATUS_STARTED      = 'started';
    public const STATUS_COMPLETED    = 'completed';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_COMPENSATING = 'compensating';
    public const STATUS_COMPENSATED  = 'compensated';

    protected $fillable = [
        'saga_id',
        'saga_type',
        'status',
        'current_step',
        'context',
        'compensation_log',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'context'          => 'array',
        'compensation_log' => 'array',
        'completed_at'     => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeStuck(Builder $query, int $minutesThreshold = 30): Builder
    {
        return $query->where('status', self::STATUS_STARTED)
                     ->where('created_at', '<', now()->subMinutes($minutesThreshold));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPENSATED,
            self::STATUS_FAILED,
        ]);
    }
}
