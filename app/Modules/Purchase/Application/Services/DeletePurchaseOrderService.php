<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class DeletePurchaseOrderService extends BaseService implements DeletePurchaseOrderServiceInterface
{
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseOrderNotFoundException($id);
        }

        return $this->repo->delete($id);
    }
}
