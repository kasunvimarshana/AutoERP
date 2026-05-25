<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class CashRegisterModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasActiveScope, SoftDeletes;

    protected $table = 'cash_registers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'cash_account_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
