<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class FiscalYear extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'fiscal_years';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('is_current', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\FiscalPeriod',
            'fiscal_year_id'
        );
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Budget',
            'fiscal_year_id'
        );
    }
}
