<?php

namespace Modules\ProjectManagement\Application\UseCases;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\ProjectManagement\Domain\Contracts\TaskRepositoryInterface;
use Modules\ProjectManagement\Domain\Events\TaskCompleted;

class CompleteTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $repo,
    ) {}

    public function execute(string $taskId, ?string $actualHours = null): object
    {
        return DB::transaction(function () use ($taskId, $actualHours) {
            $task = $this->repo->findById($taskId);

            if (! $task) {
                throw new ModelNotFoundException("Task [{$taskId}] not found.");
            }

            $updateData = ['status' => 'done'];

            if ($actualHours !== null) {
                $updateData['actual_hours'] = bcadd((string) $task->actual_hours, (string) $actualHours, 2);
            }

            $task = $this->repo->update($taskId, $updateData);

            Event::dispatch(new TaskCompleted($task->id, $task->project_id, $task->tenant_id));

            return $task;
        });
    }
}
