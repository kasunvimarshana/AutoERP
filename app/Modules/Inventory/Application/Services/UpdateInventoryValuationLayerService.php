<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\UpdateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Domain\Exceptions\InventoryValuationLayerNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class UpdateInventoryValuationLayerService extends BaseService implements UpdateInventoryValuationLayerServiceInterface
{
    public function __construct(private readonly InventoryValuationLayerRepositoryInterface $layerRepository)
    {
        parent::__construct($layerRepository);
    }

    protected function handle(array $data): mixed
    {
        $id    = $data['id'];
        $layer = $this->layerRepository->find($id);

        if (! $layer) {
            throw new InventoryValuationLayerNotFoundException($id);
        }

        return $this->layerRepository->delete($id);
    }
}
