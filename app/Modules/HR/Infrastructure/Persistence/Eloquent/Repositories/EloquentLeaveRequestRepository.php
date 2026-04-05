<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveRequestModel;

class EloquentLeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(private readonly LeaveRequestModel $model) {}

    private function toEntity(LeaveRequestModel $m): LeaveRequest
    {
        return new LeaveRequest(
            $m->id,
            $m->tenant_id,
            $m->employee_id,
            $m->leave_type_id,
            $m->start_date,
            $m->end_date,
            (float) $m->total_days,
            $m->status,
            $m->reason,
            $m->approved_by_id,
            $m->approved_at,
            $m->rejection_reason,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?LeaveRequest
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findPendingByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): LeaveRequest
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?LeaveRequest
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
