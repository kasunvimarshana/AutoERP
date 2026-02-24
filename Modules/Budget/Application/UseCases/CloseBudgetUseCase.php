<?php

namespace Modules\Budget\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Domain\Events\BudgetClosed;

class CloseBudgetUseCase
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepo,
    ) {}

    public function execute(string $budgetId): object
    {
        return DB::transaction(function () use ($budgetId) {
            $budget = $this->budgetRepo->findById($budgetId);

            if (! $budget) {
                throw new \DomainException('Budget not found.');
            }

            if ($budget->status === 'closed') {
                throw new \DomainException('Budget is already closed.');
            }

            $budget = $this->budgetRepo->update($budgetId, ['status' => 'closed']);

            Event::dispatch(new BudgetClosed(
                $budget->id,
                $budget->tenant_id,
            ));

            return $budget;
        });
    }
}
