<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    // Immutable - no updated_at tracking
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'status',
        'trace_id',
        'occurred_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Override to prevent updates on immutable log
    public function update(array $attributes = [], array $options = []): bool
    {
        return false;
    }
}
