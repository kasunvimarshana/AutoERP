<?php

namespace Modules\ProjectManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Events\ProjectCreated;

class CreateProjectUseCase
{
    public function __construct(
        private ProjectRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $project = $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'status'    => $data['status'] ?? 'planning',
                'budget'    => isset($data['budget']) ? bcadd((string) $data['budget'], '0', 8) : '0.00000000',
                'spent'     => '0.00000000',
            ]));

            Event::dispatch(new ProjectCreated($project->id, $project->tenant_id, $project->name));

            return $project;
        });
    }
}
