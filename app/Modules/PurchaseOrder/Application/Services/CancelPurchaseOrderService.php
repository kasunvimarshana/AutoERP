<?php
namespace Modules\PurchaseOrder\Application\Services;
use Illuminate\Support\Facades\Event;
use Modules\PurchaseOrder\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderCancelled;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderStatus;

class CancelPurchaseOrderService implements CancelPurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repository) {}

    public function execute(int $poId): PurchaseOrder
    {
        $po = $this->repository->findById($poId);
        if (!$po) throw new \DomainException("Purchase order not found: {$poId}");
        if (in_array($po->status, [PurchaseOrderStatus::RECEIVED, PurchaseOrderStatus::CLOSED], true)) {
            throw new \DomainException("Cannot cancel a purchase order with status: {$po->status}");
        }
        $po = $this->repository->update($po, ['status' => PurchaseOrderStatus::CANCELLED]);
        Event::dispatch(new PurchaseOrderCancelled($po->tenantId, $po->id));
        return $po;
    }
}
