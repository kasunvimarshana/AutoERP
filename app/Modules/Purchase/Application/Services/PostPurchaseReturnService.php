<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\Events\PurchaseReturnPosted;
use Modules\Purchase\Domain\Exceptions\PurchaseReturnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;

class PostPurchaseReturnService extends BaseService implements PostPurchaseReturnServiceInterface
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repo,
        private readonly PurchaseReturnLineRepositoryInterface $purchaseReturnLineRepository,
    ) {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseReturn
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseReturnNotFoundException($id);
        }

        if ($entity->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Purchase return cannot be posted in its current state.');
        }

        $entity->post();
        $saved = $this->repo->save($entity);

        $lines = $this->purchaseReturnLineRepository->findByPurchaseReturnId((int) $saved->getId());

        $this->addEvent(new PurchaseReturnPosted(
            tenantId: $saved->getTenantId(),
            purchaseReturnId: (int) $saved->getId(),
            supplierId: $saved->getSupplierId(),
            lines: $lines->map(fn ($l) => [
                'id' => $l->getId(),
                'product_id' => $l->getProductId(),
                'from_location_id' => $l->getFromLocationId(),
                'uom_id' => $l->getUomId(),
                'return_qty' => $l->getReturnQty(),
                'unit_cost' => $l->getUnitCost(),
                'variant_id' => $l->getVariantId(),
                'batch_id' => $l->getBatchId(),
                'serial_id' => $l->getSerialId(),
            ])->values()->all(),
        ));

        return $saved;
    }
}
