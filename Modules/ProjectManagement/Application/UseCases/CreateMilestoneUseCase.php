<?php

namespace Modules\ProjectManagement\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\ProjectManagement\Domain\Contracts\MilestoneRepositoryInterface;
use Modules\ProjectManagement\Domain\Contracts\ProjectRepositoryInterface;
use Modules\ProjectManagement\Domain\Events\MilestoneCreated;

class CreateMilestoneUseCase
{
    public function __construct(
        private MilestoneRepositoryInterface $repo,
        private ProjectRepositoryInterface   $projectRepo,
    ) {}

    public function execute(array $data): object
    {
        if (empty($data['name'])) {
            throw new DomainException('Milestone name is required.');
        }

        if (empty($data['project_id'])) {
            throw new DomainException('Project ID is required.');
        }

        if (empty($data['due_date'])) {
            throw new DomainException('Due date is required.');
        }

        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $project = $this->projectRepo->findById($data['project_id']);
            if (! $project) {
                throw new DomainException("Project [{$data['project_id']}] not found.");
            }

            $milestone = $this->repo->create(array_merge($data, [
                'tenant_id' => $tenantId,
                'status'    => $data['status'] ?? 'pending',
            ]));

            Event::dispatch(new MilestoneCreated(
                $milestone->id,
                $milestone->project_id,
                $milestone->tenant_id,
                $milestone->name,
            ));

            return $milestone;
        });
    }
}
