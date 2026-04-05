<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Application\Services;
use Modules\PurchaseOrder\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderConfirmed;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
class ConfirmPurchaseOrderService implements ConfirmPurchaseOrderServiceInterface {
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo) {}
    public function execute(int $id): PurchaseOrder {
        $po = $this->repo->findById($id);
        if (!$po) throw new PurchaseOrderNotFoundException($id);
        $po->confirm();
        $this->repo->updateStatus($id, 'confirmed');
        event(new PurchaseOrderConfirmed($po->getTenantId(), $id));
        return $this->repo->findById($id);
    }
}
