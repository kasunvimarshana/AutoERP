<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;

/**
 * Deducts days from an approved leave allocation when a leave request is approved.
 *
 * This use case is triggered by the LeaveRequestApproved domain event listener,
 * or can be called directly from ApproveLeaveRequestUseCase if the Leave module
 * enforces balance checking.
 */
class DeductLeaveAllocationUseCase
{
    public function __construct(
        private LeaveAllocationRepositoryInterface $allocationRepo,
    ) {}

    public function execute(string $allocationId, string $daysToDeduct): object
    {
        return DB::transaction(function () use ($allocationId, $daysToDeduct) {
            $allocation = $this->allocationRepo->findById($allocationId);

            if (! $allocation) {
                throw new DomainException('Leave allocation not found.');
            }

            if ($allocation->status !== 'approved') {
                throw new DomainException('Leave allocation is not in approved status.');
            }

            $remaining = bcsub(
                (string) $allocation->total_days,
                (string) $allocation->used_days,
                2,
            );

            if (bccomp($daysToDeduct, $remaining, 2) > 0) {
                throw new DomainException('Insufficient leave balance: requested ' . $daysToDeduct . ' days, ' . $remaining . ' remaining.');
            }

            $newUsed = bcadd((string) $allocation->used_days, $daysToDeduct, 2);

            return $this->allocationRepo->update($allocationId, [
                'used_days' => $newUsed,
            ]);
        });
    }
}
