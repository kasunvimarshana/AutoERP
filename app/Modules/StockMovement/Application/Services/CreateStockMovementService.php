<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\Events\StockMovementCreated;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class CreateStockMovementService extends BaseService implements CreateStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    protected function handle(array $data): StockMovement
    {
        $dto = StockMovementData::fromArray($data);

        $movementDate = $dto->movementDate ? new \DateTimeImmutable($dto->movementDate) : null;

        $movement = new StockMovement(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber,
            movementType:    $dto->movementType,
            productId:       $dto->productId,
            quantity:        $dto->quantity,
            variationId:     $dto->variationId,
            fromLocationId:  $dto->fromLocationId,
            toLocationId:    $dto->toLocationId,
            batchId:         $dto->batchId,
            serialNumberId:  $dto->serialNumberId,
            uomId:           $dto->uomId,
            unitCost:        $dto->unitCost,
            currency:        $dto->currency,
            referenceType:   $dto->referenceType,
            referenceId:     $dto->referenceId,
            performedBy:     $dto->performedBy,
            movementDate:    $movementDate,
            notes:           $dto->notes,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
            status:          $dto->status,
        );

        $saved = $this->movementRepository->save($movement);
        $this->addEvent(new StockMovementCreated($saved));

        return $saved;
    }
}
