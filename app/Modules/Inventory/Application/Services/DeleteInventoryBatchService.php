<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventoryBatchServiceInterface;
use Modules\Inventory\Domain\Events\InventoryBatchDeleted;
use Modules\Inventory\Domain\Exceptions\InventoryBatchNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;

class DeleteInventoryBatchService extends BaseService implements DeleteInventoryBatchServiceInterface
{
    public function __construct(private readonly InventoryBatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): bool
    {
        $id    = $data['id'];
        $batch = $this->batchRepository->find($id);

        if (! $batch) {
            throw new InventoryBatchNotFoundException($id);
        }

        $this->addEvent(new InventoryBatchDeleted($batch));

        return $this->batchRepository->delete($id);
    }
}
