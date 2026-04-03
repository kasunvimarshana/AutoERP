<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\StockMovement\Application\Contracts\DeleteStockMovementServiceInterface;
use Modules\StockMovement\Domain\Events\StockMovementDeleted;
use Modules\StockMovement\Domain\Exceptions\StockMovementNotFoundException;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class DeleteStockMovementService extends BaseService implements DeleteStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    protected function handle(array $data): bool
    {
        $id       = $data['id'];
        $movement = $this->movementRepository->find($id);

        if (! $movement) {
            throw new StockMovementNotFoundException($id);
        }

        $this->addEvent(new StockMovementDeleted($movement));

        return $this->movementRepository->delete($id);
    }
}
