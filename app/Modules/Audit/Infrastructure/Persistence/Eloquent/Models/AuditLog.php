<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'occurred_at';
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'tags' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForOrganizationUnit(Builder $query, int $organizationUnitId): Builder
    {
        return $query->where('organization_unit_id', $organizationUnitId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'user_id'
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_id'
        );
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'organization_unit_id'
        );
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'auditable_type', 'auditable_id');
    }
}
