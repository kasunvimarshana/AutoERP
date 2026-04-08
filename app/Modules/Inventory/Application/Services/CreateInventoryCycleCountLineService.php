<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryCycleCountLineData;
use Modules\Inventory\Domain\Entities\InventoryCycleCountLine;
use Modules\Inventory\Domain\Events\InventoryCycleCountLineRecorded;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;

class CreateInventoryCycleCountLineService extends BaseService implements CreateInventoryCycleCountLineServiceInterface
{
    public function __construct(private readonly InventoryCycleCountLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): InventoryCycleCountLine
    {
        $dto = InventoryCycleCountLineData::fromArray($data);

        $line = new InventoryCycleCountLine(
            tenantId:       $dto->tenantId,
            cycleCountId:   $dto->cycleCountId,
            productId:      $dto->productId,
            expectedQty:    $dto->expectedQty,
            variationId:    $dto->variationId,
            batchId:        $dto->batchId,
            serialNumberId: $dto->serialNumberId,
            locationId:     $dto->locationId,
            notes:          $dto->notes,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new InventoryCycleCountLineRecorded($saved));

        return $saved;
    }
}
