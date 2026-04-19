<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLogModel extends BaseModel
{

    use HasTenant;
    public const CREATED_AT = 'occurred_at';

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
        'tenant_id' => 'int',
        'user_id' => 'int',
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
