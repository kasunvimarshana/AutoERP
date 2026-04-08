<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderDeleted;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class DeletePurchaseOrderService extends BaseService implements DeletePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $orderRepository)
    {
        parent::__construct($orderRepository);
    }

    protected function handle(array $data): bool
    {
        $id    = $data['id'];
        $order = $this->orderRepository->find($id);

        if (! $order) {
            throw new PurchaseOrderNotFoundException($id);
        }

        $this->addEvent(new PurchaseOrderDeleted($id));

        return $this->orderRepository->delete($id);
    }
}
