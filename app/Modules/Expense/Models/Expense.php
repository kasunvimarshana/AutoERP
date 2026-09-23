<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;

final class Expense extends TenantOwnedModel
{
    protected $table = 'expenses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'expense_type_id' => 'integer',
            'currency_id' => 'integer',
            'payment_method_id_snapshot' => 'integer',
            'expense_date' => 'date',
            'instrument_date' => 'date',
            'amount' => 'decimal:6',
            'exchange_rate' => 'decimal:6',
            'status' => ExpenseStatus::class,
            'created_by' => 'integer',
            'posted_by' => 'integer',
            'posted_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }
}
