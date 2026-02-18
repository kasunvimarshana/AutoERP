<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Immutable Audit Log Model
 * Records all important system events for compliance and troubleshooting
 */
class AuditLog extends Model
{
    // No soft deletes - audit logs are immutable
    public $timestamps = false; // Only created_at
    
    protected $fillable = [
        'audit_id',
        'user_id',
        'tenant_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Prevent updates to audit logs (immutable)
     */
    public static function boot()
    {
        parent::boot();
        
        static::updating(function () {
            return false; // Prevent updates
        });
        
        static::deleting(function () {
            return false; // Prevent deletions
        });
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by auditable type
     */
    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('auditable_type', $modelClass);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by tag
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Get the changes made
     */
    public function getChanges(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }
        
        return $changes;
    }

    /**
     * Get human-readable event description
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user ? $this->user->name : 'System';
        $model = class_basename($this->auditable_type);
        
        return match ($this->event) {
            'created' => "{$user} created {$model} #{$this->auditable_id}",
            'updated' => "{$user} updated {$model} #{$this->auditable_id}",
            'deleted' => "{$user} deleted {$model} #{$this->auditable_id}",
            'viewed' => "{$user} viewed {$model} #{$this->auditable_id}",
            'exported' => "{$user} exported {$model} data",
            default => "{$user} performed {$this->event} on {$model} #{$this->auditable_id}",
        };
    }
}
