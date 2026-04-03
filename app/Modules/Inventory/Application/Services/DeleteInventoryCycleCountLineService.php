<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Domain\Exceptions\InventoryCycleCountLineNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;

class DeleteInventoryCycleCountLineService extends BaseService implements DeleteInventoryCycleCountLineServiceInterface
{
    public function __construct(private readonly InventoryCycleCountLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new InventoryCycleCountLineNotFoundException($id);
        }

        return $this->lineRepository->delete($id);
    }
}
