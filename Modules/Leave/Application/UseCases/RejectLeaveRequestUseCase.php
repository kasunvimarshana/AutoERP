<?php

namespace Modules\Leave\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveRequestRejected;

class RejectLeaveRequestUseCase
{
    public function __construct(
        private LeaveRequestRepositoryInterface $requestRepo,
    ) {}

    public function execute(string $requestId, string $reviewerId, ?string $reason = null): object
    {
        return DB::transaction(function () use ($requestId, $reviewerId, $reason) {
            $request = $this->requestRepo->findById($requestId);

            if (! $request) {
                throw new DomainException('Leave request not found.');
            }

            if ($request->status !== 'draft') {
                throw new DomainException('Only draft leave requests can be rejected.');
            }

            $updated = $this->requestRepo->update($requestId, [
                'status'          => 'rejected',
                'reviewer_id'     => $reviewerId,
                'reviewed_at'     => now(),
                'rejection_reason' => $reason,
            ]);

            Event::dispatch(new LeaveRequestRejected($requestId, $request->tenant_id, $reviewerId));

            return $updated;
        });
    }
}
