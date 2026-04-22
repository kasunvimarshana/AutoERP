<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class CancelPurchaseOrderService extends BaseService implements CancelPurchaseOrderServiceInterface
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
            throw new \InvalidArgumentException('Purchase order cannot be cancelled in its current state.');
        }

        $entity->cancel();

        return $this->repo->save($entity);
    }
}
