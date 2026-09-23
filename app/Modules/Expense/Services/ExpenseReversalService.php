<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Expense\Models\Expense;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;

final class ExpenseReversalService
{
    public function __construct(private readonly FinanceSourceReversalInterface $finance) {}

    public function reverse(
        Expense $expense,
        int $expectedVersion,
        string $reversalDate,
        string $reason,
        ?int $reversedBy,
    ): Expense {
        return DB::transaction(function () use ($expense, $expectedVersion, $reversalDate, $reason, $reversedBy): Expense {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->getKey());
            if ($expectedVersion < 1 || (int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Expense was changed by another request. Reload it before reversing.');
            }
            $status = $locked->status instanceof ExpenseStatus
                ? $locked->status
                : ExpenseStatus::from((string) $locked->status);
            if ($status !== ExpenseStatus::Posted) {
                throw new InvalidArgumentException('Only a posted expense can be reversed.');
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw new InvalidArgumentException('Expense reversal reason is required.');
            }

            $result = $this->finance->reverseSource(
                (int) $locked->tenant_id,
                (int) $locked->organization_unit_id,
                'expense',
                'expense',
                (int) $locked->getKey(),
                $reversalDate,
                $reversedBy,
                $reason,
            );
            $locked->forceFill([
                'status' => ExpenseStatus::Reversed->value,
                'finance_reversal_reference' => $result->journalNumber,
                'reversal_date' => $reversalDate,
                'reversal_reason' => $reason,
                'reversed_by' => $reversedBy,
                'reversed_at' => now(),
                'row_version' => (int) $locked->row_version + 1,
            ])->save();

            return $locked->refresh()->load(['expenseType', 'organizationUnit', 'currency']);
        }, 3);
    }
}
