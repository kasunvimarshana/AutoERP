<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class RecurringVoucherModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasActiveScope, SoftDeletes;

    protected $table = 'recurring_vouchers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'next_run_date' => 'date',
            'party_id' => 'integer',
            'row_version' => 'integer',
            'start_date' => 'date',
            'tax_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'total_amount' => 'decimal:4',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function contraAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'contra_account_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRateModel::class, 'tax_rate_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
