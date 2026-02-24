<?php

namespace Modules\ProjectManagement\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\ProjectManagement\Domain\Contracts\TimeEntryRepositoryInterface;
use Modules\ProjectManagement\Infrastructure\Models\TimeEntryModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class TimeEntryRepository extends BaseEloquentRepository implements TimeEntryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new TimeEntryModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = TimeEntryModel::query();

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('entry_date', 'desc')->paginate($perPage);
    }

    public function findByProject(string $projectId): Collection
    {
        return TimeEntryModel::where('project_id', $projectId)
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    public function sumHoursByProject(string $projectId): string
    {
        $total = '0.00';
        TimeEntryModel::where('project_id', $projectId)
            ->chunk(100, function (Collection $entries) use (&$total) {
                foreach ($entries as $entry) {
                    $total = bcadd($total, (string) $entry->hours, 2);
                }
            });

        return $total;
    }
}
