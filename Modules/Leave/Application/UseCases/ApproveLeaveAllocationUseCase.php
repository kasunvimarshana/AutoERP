<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveAllocationApproved;

class ApproveLeaveAllocationUseCase
{
    public function __construct(
        private LeaveAllocationRepositoryInterface $allocationRepo,
    ) {}

    public function execute(string $allocationId, string $approverId): object
    {
        return DB::transaction(function () use ($allocationId, $approverId) {
            $allocation = $this->allocationRepo->findById($allocationId);

            if (! $allocation) {
                throw new DomainException('Leave allocation not found.');
            }

            if ($allocation->status !== 'draft') {
                throw new DomainException('Only draft leave allocations can be approved.');
            }

            $updated = $this->allocationRepo->update($allocationId, [
                'status'      => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            Event::dispatch(new LeaveAllocationApproved(
                $allocationId,
                $allocation->tenant_id,
                $approverId,
            ));

            return $updated;
        });
    }
}
