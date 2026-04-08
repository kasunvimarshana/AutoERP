<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryBatchData;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Events\InventoryBatchCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;

class CreateInventoryBatchService extends BaseService implements CreateInventoryBatchServiceInterface
{
    public function __construct(private readonly InventoryBatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): InventoryBatch
    {
        $dto = InventoryBatchData::fromArray($data);

        $manufactureDate  = $dto->manufactureDate  ? new \DateTimeImmutable($dto->manufactureDate)  : null;
        $expiryDate       = $dto->expiryDate       ? new \DateTimeImmutable($dto->expiryDate)       : null;
        $bestBeforeDate   = $dto->bestBeforeDate   ? new \DateTimeImmutable($dto->bestBeforeDate)   : null;

        $batch = new InventoryBatch(
            tenantId:         $dto->tenantId,
            productId:        $dto->productId,
            batchNumber:      $dto->batchNumber,
            variationId:      $dto->variationId,
            lotNumber:        $dto->lotNumber,
            manufactureDate:  $manufactureDate,
            expiryDate:       $expiryDate,
            bestBeforeDate:   $bestBeforeDate,
            supplierId:       $dto->supplierId,
            supplierBatchRef: $dto->supplierBatchRef,
            initialQty:       $dto->initialQty,
            remainingQty:     $dto->initialQty,
            unitCost:         $dto->unitCost,
            currency:         $dto->currency,
            status:           $dto->status,
            notes:            $dto->notes,
            metadata:         $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->batchRepository->save($batch);
        $this->addEvent(new InventoryBatchCreated($saved));

        return $saved;
    }
}
