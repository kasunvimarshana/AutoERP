<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;

final class Expense extends TenantOwnedModel
{
    protected $table = 'expenses';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (Expense $expense): void {
            $originalStatus = $expense->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof ExpenseStatus
                ? $originalStatus
                : ($originalStatus !== null ? ExpenseStatus::tryFrom((string) $originalStatus) : null);

            if ($originalStatusEnum === ExpenseStatus::Posted || $originalStatusEnum === ExpenseStatus::Reversed) {
                $immutableAttributes = [
                    'expense_date',
                    'amount',
                    'currency_id',
                    'exchange_rate',
                    'expense_type_id',
                    'tenant_id',
                    'organization_unit_id',
                ];
                if ($expense->isDirty($immutableAttributes)) {
                    throw new LogicException('Posted expenses are immutable in place. Use governed reversal instead.');
                }
            }
        });

        self::deleting(static function (): never {
            throw new LogicException('Expenses cannot be deleted. Use governed reversal instead.');
        });
    }

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
