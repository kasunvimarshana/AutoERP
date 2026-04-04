<?php
namespace Modules\PurchaseOrder\Application\Services;
use Illuminate\Support\Facades\Event;
use Modules\PurchaseOrder\Application\Contracts\ApprovePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderApproved;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderStatus;

class ApprovePurchaseOrderService implements ApprovePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repository) {}

    public function execute(int $poId, int $approvedBy): PurchaseOrder
    {
        $po = $this->repository->findById($poId);
        if (!$po) throw new \DomainException("Purchase order not found: {$poId}");
        if ($po->status === PurchaseOrderStatus::CANCELLED) {
            throw new \DomainException("Cannot approve a cancelled purchase order.");
        }
        $po = $this->repository->update($po, [
            'status'      => PurchaseOrderStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
        Event::dispatch(new PurchaseOrderApproved($po->tenantId, $po->id));
        return $po;
    }
}
