<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\LeaveRequestServiceInterface;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestApproved;
use Modules\HR\Domain\Events\LeaveRequestCreated;
use Modules\HR\Domain\Events\LeaveRequestRejected;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class LeaveRequestService implements LeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repository) {}

    public function findById(int $id): LeaveRequest
    {
        $request = $this->repository->findById($id);
        if ($request === null) {
            throw new LeaveRequestNotFoundException($id);
        }
        return $request;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByEmployee($employeeId, $perPage, $page);
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findPendingByTenant(int $tenantId): array
    {
        return $this->repository->findPendingByTenant($tenantId);
    }

    public function create(array $data): LeaveRequest
    {
        $request = $this->repository->create($data);
        event(new LeaveRequestCreated($request->getId(), $request->getEmployeeId()));
        return $request;
    }

    public function approve(int $id, int $approverId): LeaveRequest
    {
        $request = $this->findById($id);
        $request->approve($approverId);
        $updated = $this->repository->update($id, [
            'status'       => LeaveRequest::STATUS_APPROVED,
            'approved_by_id' => $approverId,
            'approved_at'  => $request->getApprovedAt()->format('Y-m-d H:i:s'),
        ]);
        $request = $updated ?? $request;
        event(new LeaveRequestApproved($request->getId(), $approverId));
        return $request;
    }

    public function reject(int $id, int $approverId, string $reason): LeaveRequest
    {
        $request = $this->findById($id);
        $request->reject($approverId, $reason);
        $updated = $this->repository->update($id, [
            'status'           => LeaveRequest::STATUS_REJECTED,
            'approved_by_id'   => $approverId,
            'approved_at'      => $request->getApprovedAt()->format('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ]);
        $request = $updated ?? $request;
        event(new LeaveRequestRejected($request->getId(), $approverId));
        return $request;
    }

    public function cancel(int $id): LeaveRequest
    {
        $request = $this->findById($id);
        $request->cancel();
        $updated = $this->repository->update($id, ['status' => LeaveRequest::STATUS_CANCELLED]);
        return $updated ?? $request;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
