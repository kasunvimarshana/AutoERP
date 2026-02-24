<?php

namespace Modules\Budget\Application\UseCases;

use DomainException;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;

class GetBudgetVarianceReportUseCase
{
    public function __construct(
        private BudgetRepositoryInterface     $budgetRepo,
        private BudgetLineRepositoryInterface $lineRepo,
    ) {}

    /**
     * Return a variance report for the given budget.
     *
     * Each line item includes:
     *  - planned_amount
     *  - actual_amount
     *  - variance       (planned − actual; negative = overspent)
     *  - utilisation_pct (actual ÷ planned × 100, scale 2; null when planned = 0)
     *  - overspent      (bool)
     */
    public function execute(string $budgetId): array
    {
        $budget = $this->budgetRepo->findById($budgetId);
        if (! $budget) {
            throw new DomainException('Budget not found.');
        }

        $lines = $this->lineRepo->findByBudget($budgetId);

        $totalPlanned = '0.00000000';
        $totalActual  = '0.00000000';

        $lineItems = [];
        foreach ($lines as $line) {
            $planned  = bcadd((string) $line->planned_amount, '0', 8);
            $actual   = bcadd((string) $line->actual_amount, '0', 8);
            $variance = bcsub($planned, $actual, 8);
            $overspent = bccomp($actual, $planned, 8) > 0;

            $utilisationPct = null;
            if (bccomp($planned, '0', 8) !== 0) {
                $utilisationPct = bcdiv(bcmul($actual, '100', 8), $planned, 2);
            }

            $lineItems[] = [
                'id'              => $line->id,
                'category'        => $line->category,
                'description'     => $line->description,
                'planned_amount'  => $planned,
                'actual_amount'   => $actual,
                'variance'        => $variance,
                'utilisation_pct' => $utilisationPct,
                'overspent'       => $overspent,
            ];

            $totalPlanned = bcadd($totalPlanned, $planned, 8);
            $totalActual  = bcadd($totalActual, $actual, 8);
        }

        $totalVariance = bcsub($totalPlanned, $totalActual, 8);

        return [
            'budget_id'      => $budgetId,
            'budget_name'    => $budget->name,
            'budget_status'  => $budget->status,
            'total_planned'  => $totalPlanned,
            'total_actual'   => $totalActual,
            'total_variance' => $totalVariance,
            'overspent'      => bccomp($totalActual, $totalPlanned, 8) > 0,
            'lines'          => $lineItems,
        ];
    }
}
