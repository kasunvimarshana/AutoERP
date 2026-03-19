<?php

namespace Enterprise\Core\Security;

use Illuminate\Database\Eloquent\Model;

/**
 * ImmutableAuditLog - Captures all sensitive domain changes.
 * Append-only records for long-term regulatory retention.
 */
class ImmutableAuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Prevent updates or deletions.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::updating(function ($model) {
            throw new \Exception("Audit logs are immutable and cannot be updated.");
        });

        static::deleting(function ($model) {
            throw new \Exception("Audit logs are immutable and cannot be deleted.");
        });
    }

    /**
     * Log a change event.
     */
    public static function log(string $entity, string $action, array $old = [], array $new = [], array $metadata = [])
    {
        return self::create([
            'tenant_id' => $metadata['tenant_id'] ?? null,
            'user_id' => $metadata['user_id'] ?? null,
            'entity' => $entity,
            'action' => $action, // e.g., 'CREATED', 'UPDATED', 'DELETED', 'LOGIN'
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
