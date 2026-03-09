<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Saga Record Entity
 *
 * Persists saga transaction state for query and audit.
 */
class SagaRecord extends Model
{
    protected $table = 'saga_records';

    protected $fillable = [
        'saga_id',
        'saga_type',
        'status',
        'current_step',
        'completed_steps',
        'failed_step',
        'context',
        'error_message',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'context' => 'array',
    ];
}
