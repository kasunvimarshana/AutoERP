<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\UpdateInventoryLevelServiceInterface;
use Modules\Inventory\Domain\Events\InventoryLevelUpdated;
use Modules\Inventory\Domain\Exceptions\InventoryLevelNotFoundException;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class UpdateInventoryLevelService extends BaseService implements UpdateInventoryLevelServiceInterface
{
    public function __construct(private readonly InventoryLevelRepositoryInterface $levelRepository)
    {
        parent::__construct($levelRepository);
    }

    protected function handle(array $data): mixed
    {
        $id    = $data['id'];
        $level = $this->levelRepository->find($id);

        if (! $level) {
            throw new InventoryLevelNotFoundException($id);
        }

        $level->updateQuantities(
            qtyOnHand:   (float) ($data['qty_on_hand']  ?? $level->getQtyOnHand()),
            qtyReserved: (float) ($data['qty_reserved'] ?? $level->getQtyReserved()),
            qtyOnOrder:  (float) ($data['qty_on_order'] ?? $level->getQtyOnOrder()),
        );

        $saved = $this->levelRepository->save($level);
        $this->addEvent(new InventoryLevelUpdated($saved));

        return $saved;
    }
}
