<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventorySerialNumberServiceInterface;
use Modules\Inventory\Domain\Entities\InventorySerialNumber;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;

class FindInventorySerialNumberService extends BaseService implements FindInventorySerialNumberServiceInterface
{
    public function __construct(private readonly InventorySerialNumberRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    public function findBySerial(int $tenantId, int $productId, string $serial): ?InventorySerialNumber
    {
        return $this->serialRepository->findBySerial($tenantId, $productId, $serial);
    }

    public function findByLocation(int $tenantId, int $locationId): Collection
    {
        return $this->serialRepository->findByLocation($tenantId, $locationId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
