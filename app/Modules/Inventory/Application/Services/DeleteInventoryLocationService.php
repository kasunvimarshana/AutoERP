<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventoryLocationServiceInterface;
use Modules\Inventory\Domain\Events\InventoryLocationDeleted;
use Modules\Inventory\Domain\Exceptions\InventoryLocationNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLocationRepositoryInterface;

class DeleteInventoryLocationService extends BaseService implements DeleteInventoryLocationServiceInterface
{
    public function __construct(private readonly InventoryLocationRepositoryInterface $locationRepository)
    {
        parent::__construct($locationRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $location = $this->locationRepository->find($id);

        if (! $location) {
            throw new InventoryLocationNotFoundException($id);
        }

        $this->addEvent(new InventoryLocationDeleted($location));

        return $this->locationRepository->delete($id);
    }
}
