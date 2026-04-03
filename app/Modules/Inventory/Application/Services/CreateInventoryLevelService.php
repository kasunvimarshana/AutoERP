<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\CreateInventoryLevelServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryLevelData;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\InventoryLevelUpdated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class CreateInventoryLevelService extends BaseService implements CreateInventoryLevelServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    protected function handle(array $data): InventoryLevel
    {
        $dto   = InventoryLevelData::fromArray($data);
        $avail = $dto->qtyOnHand - $dto->qtyReserved;

        $level = new InventoryLevel(
            tenantId:     $dto->tenantId,
            productId:    $dto->productId,
            variationId:  $dto->variationId,
            locationId:   $dto->locationId,
            batchId:      $dto->batchId,
            uomId:        $dto->uomId,
            qtyOnHand:    $dto->qtyOnHand,
            qtyReserved:  $dto->qtyReserved,
            qtyAvailable: max(0.0, $avail),
            qtyOnOrder:   $dto->qtyOnOrder,
            reorderPoint: $dto->reorderPoint,
            reorderQty:   $dto->reorderQty,
            maxQty:       $dto->maxQty,
            minQty:       $dto->minQty,
        );

        $saved = $this->levelRepository->save($level);
        $this->addEvent(new InventoryLevelUpdated($saved));

        return $saved;
    }
}
