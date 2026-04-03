<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\FindPurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;

class FindPurchaseOrderLineService extends BaseService implements FindPurchaseOrderLineServiceInterface
{
    public function __construct(private readonly PurchaseOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findByOrder(int $purchaseOrderId): Collection
    {
        return $this->lineRepository->findByOrder($purchaseOrderId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
