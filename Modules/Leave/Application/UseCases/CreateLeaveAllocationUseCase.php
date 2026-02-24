<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveAllocated;

class CreateLeaveAllocationUseCase
{
    public function __construct(
        private LeaveAllocationRepositoryInterface $allocationRepo,
        private LeaveTypeRepositoryInterface        $leaveTypeRepo,
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

            // BCMath normalisation — store days as DECIMAL(8,2)
            $totalDays = bcadd((string) $data['total_days'], '0', 2);

            if (bccomp($totalDays, '0', 2) <= 0) {
                throw new DomainException('Total days allocated must be greater than zero.');
            }

            $allocation = $this->allocationRepo->create([
                'tenant_id'    => $data['tenant_id'],
                'employee_id'  => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'total_days'   => $totalDays,
                'used_days'    => '0.00',
                'period_label' => $data['period_label'] ?? null,
                'valid_from'   => $data['valid_from'] ?? null,
                'valid_to'     => $data['valid_to'] ?? null,
                'notes'        => $data['notes'] ?? null,
                'status'       => 'draft',
            ]);

            Event::dispatch(new LeaveAllocated(
                $allocation->id,
                $allocation->tenant_id,
                $allocation->employee_id,
                $allocation->leave_type_id,
                $totalDays,
            ));

            return $allocation;
        });
    }
}
