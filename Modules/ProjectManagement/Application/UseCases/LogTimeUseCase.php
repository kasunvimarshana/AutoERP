<?php

namespace Modules\ProjectManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\TimeEntryRepositoryInterface;

class LogTimeUseCase
{
    public function __construct(
        private TimeEntryRepositoryInterface $timeEntryRepo,
        private ProjectRepositoryInterface   $projectRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $entry = $this->timeEntryRepo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'hours'     => bcadd((string) $data['hours'], '0', 2),
            ]));

            $project = $this->projectRepo->findById($data['project_id']);

            if ($project) {
                $newSpent = bcadd((string) $project->spent, (string) $entry->hours, 8);
                $this->projectRepo->update($project->id, ['spent' => $newSpent]);
            }

            return $entry;
        });
    }
}
