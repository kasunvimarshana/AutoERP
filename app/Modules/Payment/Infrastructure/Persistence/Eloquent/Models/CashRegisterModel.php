<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class CashRegisterModel extends Model
{
    use HasActiveScope, HasOrganizationUnitScope, HasReferenceScope, HasTenantScope, SoftDeletes;

    protected $table = 'cash_registers';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'name';

    protected function casts(): array
    {
        return [
            'cash_account_id' => 'integer',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
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

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'cash_account_id');
    }
}

