<?php

declare(strict_types=1);

namespace Modules\Voucher\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class RecurringVoucherModel extends Model
{
    use HasActiveScope, HasOrganizationUnitScope, HasReferenceScope, HasTenantScope, SoftDeletes;

    protected $table = 'recurring_vouchers';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'name';

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'amount' => 'decimal:4',
            'contra_account_id' => 'integer',
            'created_by' => 'integer',
            'end_date' => 'date',
            'interval' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'next_run_date' => 'date',
            'organization_unit_id' => 'integer',
            'party_id' => 'integer',
            'row_version' => 'integer',
            'start_date' => 'date',
            'tax_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_rate_id' => 'integer',
            'tenant_id' => 'integer',
            'total_amount' => 'decimal:4',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function contraAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'contra_account_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRateModel::class, 'tax_rate_id');
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }
}

