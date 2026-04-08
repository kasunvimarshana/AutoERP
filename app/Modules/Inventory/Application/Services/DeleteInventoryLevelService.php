<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventoryLevelServiceInterface;
use Modules\Inventory\Domain\Exceptions\InventoryLevelNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class DeleteInventoryLevelService extends BaseService implements DeleteInventoryLevelServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    protected function handle(array $data): bool
    {
        $id    = $data['id'];
        $level = $this->levelRepository->find($id);

        if (! $level) {
            throw new InventoryLevelNotFoundException($id);
        }

        return $this->levelRepository->delete($id);
    }
}
