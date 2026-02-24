<?php

namespace Modules\Purchase\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseRequisitionApproved;

class ApprovePurchaseRequisitionUseCase
{
    public function __construct(private PurchaseRequisitionRepositoryInterface $repo) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $requisition = $this->repo->findById($id);

            if (! $requisition) {
                throw new \RuntimeException('Purchase requisition not found.');
            }

            if (! in_array($requisition->status, ['draft', 'pending_approval'], true)) {
                throw new \RuntimeException(
                    'Only draft or pending_approval requisitions can be approved.'
                );
            }

            $approvedBy  = auth()->id();
            $updatedRequisition = $this->repo->update($id, [
                'status'      => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            Event::dispatch(new PurchaseRequisitionApproved($id, $approvedBy));

            return $updatedRequisition;
        });
    }
}
