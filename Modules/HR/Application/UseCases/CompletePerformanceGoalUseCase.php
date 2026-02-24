<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\PerformanceGoalRepositoryInterface;
use Modules\HR\Domain\Enums\GoalStatus;
use Modules\HR\Domain\Events\PerformanceGoalCompleted;

class CompletePerformanceGoalUseCase
{
    public function __construct(
        private PerformanceGoalRepositoryInterface $repo,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $goal = $this->repo->findById($id);

            if (! $goal) {
                throw new \DomainException("Performance goal not found: {$id}");
            }

            if ($goal->status === GoalStatus::Completed->value) {
                throw new \DomainException('Goal is already completed.');
            }

            if ($goal->status === GoalStatus::Cancelled->value) {
                throw new \DomainException('Cannot complete a cancelled goal.');
            }

            $goal = $this->repo->update($id, [
                'status'       => GoalStatus::Completed->value,
                'completed_at' => now()->toDateTimeString(),
            ]);

            Event::dispatch(new PerformanceGoalCompleted($id));

            return $goal;
        });
    }
}
