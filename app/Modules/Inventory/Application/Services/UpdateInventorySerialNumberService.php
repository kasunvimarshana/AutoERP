<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Inventory\Application\Contracts\UpdateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\DTOs\UpdateInventorySerialNumberData;
use Modules\Inventory\Domain\Events\InventorySerialNumberUpdated;
use Modules\Inventory\Domain\Exceptions\InventorySerialNumberNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;

class UpdateInventorySerialNumberService extends BaseService implements UpdateInventorySerialNumberServiceInterface
{
    public function __construct(private readonly InventorySerialNumberRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto    = UpdateInventorySerialNumberData::fromArray($data);
        $serial = $this->serialRepository->find($dto->id);

        if (! $serial) {
            throw new InventorySerialNumberNotFoundException($dto->id);
        }

        $purchasedAt = $dto->purchasedAt ? new \DateTimeImmutable($dto->purchasedAt) : $serial->getPurchasedAt();

        $serial->updateDetails(
            batchId:       $serial->getBatchId(),
            locationId:    $dto->locationId    ?? $serial->getLocationId(),
            status:        $dto->status        ?? $serial->getStatus(),
            purchasePrice: $dto->purchasePrice ?? $serial->getPurchasePrice(),
            currency:      $dto->currency      ?? $serial->getCurrency(),
            purchasedAt:   $purchasedAt,
            notes:         $dto->notes         ?? $serial->getNotes(),
            metadata:      $dto->metadata !== null ? new Metadata($dto->metadata) : $serial->getMetadata(),
        );

        $saved = $this->serialRepository->save($serial);
        $this->addEvent(new InventorySerialNumberUpdated($saved));

        return $saved;
    }
}
