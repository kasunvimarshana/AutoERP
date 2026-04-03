<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryValuationLayerData;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\Events\InventoryValuationLayerCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class CreateInventoryValuationLayerService extends BaseService implements CreateInventoryValuationLayerServiceInterface
{
    public function __construct(private readonly InventoryValuationLayerRepositoryInterface $layerRepository)
    {
        parent::__construct($layerRepository);
    }

    protected function handle(array $data): InventoryValuationLayer
    {
        $dto       = InventoryValuationLayerData::fromArray($data);
        $layerDate = new \DateTimeImmutable($dto->layerDate);

        $layer = new InventoryValuationLayer(
            tenantId:        $dto->tenantId,
            productId:       $dto->productId,
            layerDate:       $layerDate,
            qtyIn:           $dto->qtyIn,
            unitCost:        $dto->unitCost,
            valuationMethod: $dto->valuationMethod,
            variationId:     $dto->variationId,
            batchId:         $dto->batchId,
            locationId:      $dto->locationId,
            currency:        $dto->currency,
            referenceType:   $dto->referenceType,
            referenceId:     $dto->referenceId,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->layerRepository->save($layer);
        $this->addEvent(new InventoryValuationLayerCreated($saved));

        return $saved;
    }
}
