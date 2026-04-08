<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountServiceInterface;
use Modules\Inventory\Domain\Events\InventoryCycleCountCancelled;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;

class DeleteInventoryCycleCountService extends BaseService implements DeleteInventoryCycleCountServiceInterface
{
    public function __construct(private readonly InventoryCycleCountRepositoryInterface $cycleCountRepository)
    {
        parent::__construct($cycleCountRepository);
    }

    protected function handle(array $data): bool
    {
        $id    = $data['id'];
        $count = $this->cycleCountRepository->find($id);

        if (! $count) {
            throw new InventoryCycleCountNotFoundException($id);
        }

        $this->addEvent(new InventoryCycleCountCancelled($count));

        return $this->cycleCountRepository->delete($id);
    }
}
