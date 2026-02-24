<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;

class RequestLeaveUseCase
{
    public function __construct(
        private LeaveRequestRepositoryInterface    $requestRepo,
        private LeaveTypeRepositoryInterface       $leaveTypeRepo,
        private ?LeaveAllocationRepositoryInterface $allocationRepo = null,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $leaveType = $this->leaveTypeRepo->findById($data['leave_type_id']);

            if (! $leaveType) {
                throw new DomainException('Leave type not found.');
            }

            if (! $leaveType->is_active) {
                throw new DomainException('Leave type is not active.');
            }

            // If an allocation repository is injected, enforce balance check.
            if ($this->allocationRepo !== null) {
                $allocation = $this->allocationRepo->findApprovedByEmployeeAndType(
                    $data['tenant_id'],
                    $data['employee_id'],
                    $data['leave_type_id'],
                );

                if ($allocation !== null) {
                    $remaining = bcsub(
                        (string) $allocation->total_days,
                        (string) $allocation->used_days,
                        2,
                    );

                    $daysRequested = (string) ($data['days_requested'] ?? 0);

                    if (bccomp($daysRequested, $remaining, 2) > 0) {
                        throw new DomainException(
                            'Insufficient leave balance: requested ' . $daysRequested
                            . ' days, ' . $remaining . ' remaining.'
                        );
                    }
                }
            }

            return $this->requestRepo->create([
                'tenant_id'      => $data['tenant_id'],
                'employee_id'    => $data['employee_id'],
                'leave_type_id'  => $data['leave_type_id'],
                'start_date'     => $data['start_date'],
                'end_date'       => $data['end_date'],
                'days_requested' => $data['days_requested'],
                'reason'         => $data['reason'] ?? null,
                'status'         => 'draft',
            ]);
        });
    }
}
