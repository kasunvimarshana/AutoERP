<?php
declare(strict_types=1);
namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/** Audit logs are never soft-deleted — they use hard deletes for retention. */
class AuditLogModel extends Model
{
    public const UPDATED_AT = null; // audit logs only have created_at

    protected $table = 'audit_logs';
    protected $fillable = [
        'tenant_id', 'user_id', 'event', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'url',
    ];
    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'user_id'    => 'int',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
}
