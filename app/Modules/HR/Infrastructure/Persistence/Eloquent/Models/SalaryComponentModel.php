<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipLineModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryStructureLineModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class SalaryComponentModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasReferenceScope, HasActiveScope, SoftDeletes;

    protected $table = 'salary_components';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'code';

    protected function casts(): array
    {
        return [
            'affects_net_pay' => 'boolean',
            'default_value' => 'decimal:4',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLineModel::class, 'salary_component_id');
    }

    public function salaryStructureLines(): HasMany
    {
        return $this->hasMany(SalaryStructureLineModel::class, 'salary_component_id');
    }

}
