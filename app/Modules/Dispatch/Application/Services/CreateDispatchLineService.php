<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Dispatch\Application\Contracts\CreateDispatchLineServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchLineData;
use Modules\Dispatch\Domain\Entities\DispatchLine;
use Modules\Dispatch\Domain\Events\DispatchLineCreated;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;

class CreateDispatchLineService extends BaseService implements CreateDispatchLineServiceInterface
{
    public function __construct(private readonly DispatchLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): DispatchLine
    {
        $dto = DispatchLineData::fromArray($data);

        $line = new DispatchLine(
            tenantId:            $dto->tenantId,
            dispatchId:          $dto->dispatchId,
            productId:           $dto->productId,
            quantity:            $dto->quantity,
            salesOrderLineId:    $dto->salesOrderLineId,
            productVariantId:    $dto->productVariantId,
            description:         $dto->description,
            unitOfMeasure:       $dto->unitOfMeasure,
            warehouseLocationId: $dto->warehouseLocationId,
            batchNumber:         $dto->batchNumber,
            serialNumber:        $dto->serialNumber,
            status:              $dto->status,
            weight:              $dto->weight,
            notes:               $dto->notes,
            metadata:            $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new DispatchLineCreated($saved->getId(), $saved->getDispatchId()));

        return $saved;
    }
}
