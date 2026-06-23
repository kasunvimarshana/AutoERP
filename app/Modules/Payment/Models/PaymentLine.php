<?php

declare(strict_types=1);

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Models\FinanceAccount;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class PaymentLine extends TenantOwnedModel
{
    protected $table = 'payment_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payment_id' => 'integer',
            'payment_method_id' => 'integer',
            'internal_bank_account_id' => 'integer',
            'amount' => 'decimal:6',
            'cleared_amount' => 'decimal:6',
            'instrument_date' => 'date',
            'deposit_date' => 'date',
            'realized_date' => 'date',
            'clearing_date' => 'date',
            'bounced_date' => 'date',
            'returned_date' => 'date',
            'metadata' => 'array',
        ]);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function internalBankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'internal_bank_account_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }
}
