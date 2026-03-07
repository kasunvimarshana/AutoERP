<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SagaTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'status',
        'steps',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'steps'   => 'array',
        'payload' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------------

    const STATUS_STARTED      = 'started';
    const STATUS_IN_PROGRESS  = 'in_progress';
    const STATUS_COMPLETED    = 'completed';
    const STATUS_FAILED       = 'failed';
    const STATUS_COMPENSATING = 'compensating';
    const STATUS_COMPENSATED  = 'compensated';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPENSATED,
            self::STATUS_FAILED,
        ], true);
    }

    /**
     * Append a step result to the steps JSON array.
     */
    public function appendStep(array $stepResult): void
    {
        $steps   = $this->steps ?? [];
        $steps[] = $stepResult;

        $this->steps = $steps;
        $this->save();
    }
}
