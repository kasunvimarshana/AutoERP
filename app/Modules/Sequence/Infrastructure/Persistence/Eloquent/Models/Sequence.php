<?php

declare(strict_types=1);

namespace Modules\Sequence\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sequence extends Model
{
    protected $table = 'sequences';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'padding' => 'integer',
            'next_number' => 'integer',
            'period_type' => 'string',
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

    #[Scope]
    protected function forDocumentType(Builder $query, string $documentType): void
    {
        $query->where('document_type', $documentType);
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
}
