<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\SalaryComponentModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class PayslipLineModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'payslip_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(PayslipModel::class, 'payslip_id');
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponentModel::class, 'salary_component_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
