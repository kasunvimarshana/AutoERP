<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveRequestApproved;

class ApproveLeaveRequestUseCase
{
    public function __construct(
        private LeaveRequestRepositoryInterface  $requestRepo,
        private ?LeaveTypeRepositoryInterface    $leaveTypeRepo = null,
    ) {}

    public function execute(string $requestId, string $approverId): object
    {
        return DB::transaction(function () use ($requestId, $approverId) {
            $request = $this->requestRepo->findById($requestId);

            if (! $request) {
                throw new DomainException('Leave request not found.');
            }

            if ($request->status !== 'draft') {
                throw new DomainException('Only draft leave requests can be approved.');
            }

            $updated = $this->requestRepo->update($requestId, [
                'status'      => 'approved',
                'reviewer_id' => $approverId,
                'reviewed_at' => now(),
            ]);

            $leaveTypeName = '';
            if ($this->leaveTypeRepo !== null && isset($request->leave_type_id)) {
                $leaveType = $this->leaveTypeRepo->findById($request->leave_type_id);
                $leaveTypeName = (string) ($leaveType?->name ?? '');
            }

            Event::dispatch(new LeaveRequestApproved(
                leaveRequestId: $requestId,
                tenantId:       (string) ($request->tenant_id ?? ''),
                approverId:     $approverId,
                employeeId:     (string) ($request->employee_id ?? ''),
                startDate:      (string) ($request->start_date ?? ''),
                endDate:        (string) ($request->end_date ?? ''),
                leaveTypeName:  $leaveTypeName,
            ));

            return $updated;
        });
    }
}
