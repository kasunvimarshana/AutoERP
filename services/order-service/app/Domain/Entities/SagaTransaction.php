<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Saga Transaction Entity
 *
 * Tracks the state of each distributed Saga transaction.
 * Used for audit, replay, and compensation (rollback).
 */
class SagaTransaction extends Model
{
    protected $table = 'saga_transactions';

    protected $fillable = [
        'saga_id',
        'saga_type',     // create_order, cancel_order, etc.
        'status',        // started | completed | failed | compensating | compensated
        'current_step',
        'completed_steps',
        'failed_step',
        'context',       // JSON: all data needed for compensation
        'error_message',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'context' => 'array',
    ];
}
