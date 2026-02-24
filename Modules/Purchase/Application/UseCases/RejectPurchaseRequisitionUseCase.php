<?php

namespace Modules\Purchase\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseRequisitionRejected;

class RejectPurchaseRequisitionUseCase
{
    public function __construct(private PurchaseRequisitionRepositoryInterface $repo) {}

    public function execute(string $id, ?string $reason = null): object
    {
        return DB::transaction(function () use ($id, $reason) {
            $requisition = $this->repo->findById($id);

            if (! $requisition) {
                throw new \RuntimeException('Purchase requisition not found.');
            }

            if (! in_array($requisition->status, ['draft', 'pending_approval'], true)) {
                throw new \RuntimeException(
                    'Only draft or pending_approval requisitions can be rejected.'
                );
            }

            $rejectedBy = auth()->id();
            $updatedRequisition = $this->repo->update($id, [
                'status'           => 'rejected',
                'rejected_by'      => $rejectedBy,
                'rejection_reason' => $reason,
                'rejected_at'      => now(),
            ]);

            Event::dispatch(new PurchaseRequisitionRejected($id, $rejectedBy, $reason));

            return $updatedRequisition;
        });
    }
}
