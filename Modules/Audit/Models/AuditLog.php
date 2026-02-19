<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * AuditLog Model
 *
 * Comprehensive audit trail for all critical operations
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $user_id
 * @property string|null $organization_id
 * @property string $event
 * @property string $auditable_type
 * @property string|null $auditable_id
 * @property array $old_values
 * @property array $new_values
 * @property array $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    use HasUuids, TenantScoped;

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'organization_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(\Modules\Tenant\Models\Organization::class);
    }

    /**
     * Scope by event type
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope by auditable type
     */
    public function scopeByAuditableType($query, string $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by organization
     */
    public function scopeByOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, ?string $fromDate, ?string $toDate)
    {
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query;
    }
}
