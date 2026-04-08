<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\CreateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySerialNumberData;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;
use Modules\Inventory\Domain\Events\InventorySerialNumberCreated;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;

class CreateInventorySerialNumberService extends BaseService implements CreateInventorySerialNumberServiceInterface
{
    public function __construct(private readonly InventorySerialNumberRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): InventorySerialNumber
    {
        $dto         = InventorySerialNumberData::fromArray($data);
        $purchasedAt = $dto->purchasedAt ? new \DateTimeImmutable($dto->purchasedAt) : null;

        $serial = new InventorySerialNumber(
            tenantId:      $dto->tenantId,
            productId:     $dto->productId,
            serialNumber:  $dto->serialNumber,
            variationId:   $dto->variationId,
            batchId:       $dto->batchId,
            locationId:    $dto->locationId,
            status:        $dto->status,
            purchasePrice: $dto->purchasePrice,
            currency:      $dto->currency,
            purchasedAt:   $purchasedAt,
            notes:         $dto->notes,
            metadata:      $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->serialRepository->save($serial);
        $this->addEvent(new InventorySerialNumberCreated($saved));

        return $saved;
    }
}
