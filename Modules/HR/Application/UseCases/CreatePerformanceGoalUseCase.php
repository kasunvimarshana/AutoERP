<?php

namespace Modules\HR\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\PerformanceGoalRepositoryInterface;
use Modules\HR\Domain\Enums\GoalStatus;
use Modules\HR\Domain\Events\PerformanceGoalCreated;

class CreatePerformanceGoalUseCase
{
    public function __construct(
        private PerformanceGoalRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $goal = $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'status'    => GoalStatus::Active->value,
            ]));

            Event::dispatch(new PerformanceGoalCreated($goal->id));

            return $goal;
        });
    }
}
