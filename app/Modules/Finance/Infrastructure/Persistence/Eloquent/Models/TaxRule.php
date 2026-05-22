<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class TaxRule extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'tax_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'tax_group_id'
        );
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemCategory', 'item_category_id');
    }
}
