<?php

declare(strict_types=1);

namespace Modules\Workflow\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Workflow\Enums\ApprovalStatus;
use Modules\Workflow\Models\Approval;

class ApprovalRepository extends BaseRepository
{
    public function __construct(
        private Approval $model
    ) {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Approval::class;
    }

    public function find(int $id): ?Approval
    {
        return $this->model
            ->with(['instance.workflow', 'step', 'approver', 'delegatedTo'])
            ->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['instance.workflow', 'approver']);

        if (isset($filters['workflow_instance_id'])) {
            $query->where('workflow_instance_id', $filters['workflow_instance_id']);
        }

        if (isset($filters['approver_id'])) {
            $query->where('approver_id', $filters['approver_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getPendingForUser(int $userId): Collection
    {
        return $this->model
            ->where('status', ApprovalStatus::PENDING)
            ->where(function ($q) use ($userId) {
                $q->where('approver_id', $userId)
                    ->orWhere('delegated_to', $userId);
            })
            ->with(['instance.workflow', 'step'])
            ->orderBy('priority', 'desc')
            ->orderBy('due_at')
            ->get();
    }

    public function getOverdue(): Collection
    {
        return $this->model
            ->where('status', ApprovalStatus::PENDING)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with(['instance.workflow', 'approver'])
            ->get();
    }

    public function create(array $data): Approval
    {
        return $this->model->create($data);
    }

    public function updateApproval(Approval $approval, array $data): Approval
    {
        $approval->update($data);

        return $approval->fresh(['instance.workflow', 'step']);
    }

    public function deleteApproval(Approval $approval): bool
    {
        return $approval->delete();
    }
}
