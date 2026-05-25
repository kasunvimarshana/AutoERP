<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class FiscalYearModel extends Model
{
    use HasOrganizationUnitScope, HasReferenceScope, HasStatusScope, HasTenantScope, SoftDeletes;

    protected $table = 'fiscal_years';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'name';

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'start_date' => 'date',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function fiscalPeriods(): HasMany
    {
        return $this->hasMany(FiscalPeriodModel::class, 'fiscal_year_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(BudgetModel::class, 'fiscal_year_id');
    }
}

