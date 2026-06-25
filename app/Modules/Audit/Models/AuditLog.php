<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use LogicException;
use Modules\Core\Models\CoreModel;

final class AuditLog extends CoreModel
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'event_uuid',
        'producer_key',
        'producer_fingerprint',
        'tenant_id',
        'tenant_name',
        'organization_unit_id',
        'organization_unit_name',
        'event_category',
        'event_name',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_guard',
        'actor_provider',
        'application_id',
        'impersonator_user_id',
        'subject_type',
        'subject_id',
        'subject_reference',
        'source_module',
        'source_type',
        'source_id',
        'source_reference',
        'changes',
        'metadata',
        'tags',
        'request_id',
        'request_method',
        'route_name',
        'route_path',
        'ip_address',
        'user_agent',
        'occurred_at',
        'recorded_at',
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Audit logs are immutable.'));
        static::deleting(static fn (): never => throw new LogicException('Audit logs cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'impersonator_user_id' => 'integer',
            'changes' => 'array',
            'metadata' => 'array',
            'tags' => 'array',
            'occurred_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
