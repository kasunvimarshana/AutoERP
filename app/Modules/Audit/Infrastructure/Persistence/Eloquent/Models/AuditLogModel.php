<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLogModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope, SoftDeletes;

    protected $table = 'audit_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'auditable_id' => 'integer',
            'metadata' => 'array',
            'new_values' => 'array',
            'occurred_at' => 'datetime',
            'old_values' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'tags' => 'array',
            'tenant_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
