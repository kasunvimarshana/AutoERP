<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Events\PurchaseOrderConfirmed;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class ConfirmPurchaseOrderService extends BaseService implements ConfirmPurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseOrder
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseOrderNotFoundException($id);
        }

        if (! in_array($entity->getStatus(), ['draft', 'sent'], true)) {
            throw new \InvalidArgumentException('Purchase order cannot be confirmed in its current state.');
        }

        $entity->confirm();
        $saved = $this->repo->save($entity);

        $this->addEvent(new PurchaseOrderConfirmed(
            tenantId: $saved->getTenantId(),
            purchaseOrderId: (int) $saved->getId(),
            supplierId: $saved->getSupplierId(),
        ));

        return $saved;
    }
}
