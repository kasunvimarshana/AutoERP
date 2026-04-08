<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class AuditLogModel extends Model
{
    use HasUuid;

    protected $table = 'audit_logs';

    public $timestamps = false;

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
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id'             => 'string',
            'tenant_id'      => 'string',
            'user_id'        => 'string',
            'old_values'     => 'array',
            'new_values'     => 'array',
            'tags'           => 'array',
            'created_at'     => 'datetime',
        ];
    }
}
