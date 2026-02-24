<?php

namespace Modules\Budget\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Domain\Events\BudgetCreated;

class CreateBudgetUseCase
{
    public function __construct(
        private BudgetRepositoryInterface     $budgetRepo,
        private BudgetLineRepositoryInterface $lineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $total = '0.00000000';
            foreach ($data['lines'] ?? [] as $line) {
                $total = bcadd($total, (string) ($line['planned_amount'] ?? '0'), 8);
            }

            $budget = $this->budgetRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'period'      => $data['period'],
                'start_date'  => $data['start_date'],
                'end_date'    => $data['end_date'],
                'total_amount' => $total,
                'status'      => 'draft',
            ]);

            foreach ($data['lines'] ?? [] as $line) {
                $this->lineRepo->create([
                    'tenant_id'      => $data['tenant_id'],
                    'budget_id'      => $budget->id,
                    'category'       => $line['category'],
                    'description'    => $line['description'] ?? null,
                    'planned_amount' => bcadd((string) ($line['planned_amount'] ?? '0'), '0', 8),
                    'actual_amount'  => '0.00000000',
                ]);
            }

            Event::dispatch(new BudgetCreated(
                $budget->id,
                $budget->tenant_id,
                $budget->name,
            ));

            return $budget;
        });
    }
}
