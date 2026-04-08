<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventorySerialNumberServiceInterface;
use Modules\Inventory\Domain\Events\InventorySerialNumberDeleted;
use Modules\Inventory\Domain\Exceptions\InventorySerialNumberNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialNumberRepositoryInterface;

class DeleteInventorySerialNumberService extends BaseService implements DeleteInventorySerialNumberServiceInterface
{
    public function __construct(private readonly InventorySerialNumberRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): bool
    {
        $id     = $data['id'];
        $serial = $this->serialRepository->find($id);

        if (! $serial) {
            throw new InventorySerialNumberNotFoundException($id);
        }

        $this->addEvent(new InventorySerialNumberDeleted($serial));

        return $this->serialRepository->delete($id);
    }
}
