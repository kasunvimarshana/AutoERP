<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Eloquent model for the `audit_logs` table.
 *
 * Note: audit logs are intentionally never soft-deleted (hard records).
 */
class AuditLogModel extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
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
        'tenant_id'  => 'int',
        'user_id'    => 'int',
        'old_values' => 'array',
        'new_values' => 'array',
        'tags'       => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Polymorphic relationship to the audited resource.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
