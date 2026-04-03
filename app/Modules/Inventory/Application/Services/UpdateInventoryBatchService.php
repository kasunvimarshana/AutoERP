<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\UpdateInventoryBatchServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventoryBatchData;
use Modules\Inventory\Domain\Events\InventoryBatchUpdated;
use Modules\Inventory\Domain\Exceptions\InventoryBatchNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;

class UpdateInventoryBatchService extends BaseService implements UpdateInventoryBatchServiceInterface
{
    public function __construct(private readonly InventoryBatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto   = UpdateInventoryBatchData::fromArray($data);
        $batch = $this->batchRepository->find($dto->id);

        if (! $batch) {
            throw new InventoryBatchNotFoundException($dto->id);
        }

        $manufactureDate = $dto->manufactureDate ? new \DateTimeImmutable($dto->manufactureDate) : $batch->getManufactureDate();
        $expiryDate      = $dto->expiryDate      ? new \DateTimeImmutable($dto->expiryDate)      : $batch->getExpiryDate();
        $bestBeforeDate  = $dto->bestBeforeDate  ? new \DateTimeImmutable($dto->bestBeforeDate)  : $batch->getBestBeforeDate();

        $batch->updateDetails(
            batchNumber:      $batch->getBatchNumber(),
            lotNumber:        $dto->lotNumber        ?? $batch->getLotNumber(),
            manufactureDate:  $manufactureDate,
            expiryDate:       $expiryDate,
            bestBeforeDate:   $bestBeforeDate,
            supplierId:       $batch->getSupplierId(),
            supplierBatchRef: $dto->supplierBatchRef ?? $batch->getSupplierBatchRef(),
            initialQty:       $batch->getInitialQty(),
            unitCost:         $dto->unitCost         ?? $batch->getUnitCost(),
            currency:         $dto->currency         ?? $batch->getCurrency(),
            status:           $dto->status           ?? $batch->getStatus(),
            notes:            $dto->notes            ?? $batch->getNotes(),
            metadata:         $dto->metadata !== null ? new Metadata($dto->metadata) : $batch->getMetadata(),
        );

        $saved = $this->batchRepository->save($batch);
        $this->addEvent(new InventoryBatchUpdated($saved));

        return $saved;
    }
}
