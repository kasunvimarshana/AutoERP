<?php

namespace Modules\Budget\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Domain\Events\BudgetLineOverspent;

class RecordActualSpendUseCase
{
    public function __construct(
        private BudgetRepositoryInterface     $budgetRepo,
        private BudgetLineRepositoryInterface $lineRepo,
    ) {}

    /**
     * Record actual spend against a budget line.
     *
     * Guards:
     *  - Budget must exist.
     *  - Budget must be approved (only track actuals against live budgets).
     *  - Line must belong to the budget.
     *  - Amount must be positive.
     *
     * Fires BudgetLineOverspent when cumulative actual exceeds planned.
     */
    public function execute(string $budgetId, string $lineId, string $amount): object
    {
        return DB::transaction(function () use ($budgetId, $lineId, $amount) {
            if (bccomp($amount, '0', 8) <= 0) {
                throw new DomainException('Spend amount must be positive.');
            }

            $budget = $this->budgetRepo->findById($budgetId);
            if (! $budget) {
                throw new DomainException('Budget not found.');
            }

            if ($budget->status !== 'approved') {
                throw new DomainException('Actual spend can only be recorded against an approved budget.');
            }

            $line = $this->lineRepo->findById($lineId);
            if (! $line || $line->budget_id !== $budgetId) {
                throw new DomainException('Budget line not found.');
            }

            $updatedLine = $this->lineRepo->addActualAmount($lineId, bcadd($amount, '0', 8));

            if (bccomp((string) $updatedLine->actual_amount, (string) $updatedLine->planned_amount, 8) > 0) {
                $overspend = bcsub(
                    (string) $updatedLine->actual_amount,
                    (string) $updatedLine->planned_amount,
                    8
                );

                Event::dispatch(new BudgetLineOverspent(
                    $updatedLine->id,
                    $budgetId,
                    $budget->tenant_id,
                    $updatedLine->category,
                    (string) $updatedLine->planned_amount,
                    (string) $updatedLine->actual_amount,
                    $overspend,
                ));
            }

            return $updatedLine;
        });
    }
}
