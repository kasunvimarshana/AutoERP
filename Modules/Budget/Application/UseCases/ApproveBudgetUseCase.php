<?php

namespace Modules\Budget\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Domain\Events\BudgetApproved;

class ApproveBudgetUseCase
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepo,
    ) {}

    public function execute(string $budgetId, string $approverId): object
    {
        return DB::transaction(function () use ($budgetId, $approverId) {
            $budget = $this->budgetRepo->findById($budgetId);

            if (! $budget) {
                throw new \DomainException('Budget not found.');
            }

            if ($budget->status !== 'draft') {
                throw new \DomainException('Only draft budgets can be approved.');
            }

            $budget = $this->budgetRepo->update($budgetId, [
                'status'      => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            Event::dispatch(new BudgetApproved(
                $budget->id,
                $budget->tenant_id,
                $approverId,
            ));

            return $budget;
        });
    }
}
