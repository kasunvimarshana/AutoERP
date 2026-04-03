<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\StockMovement\Application\Contracts\ConfirmStockMovementServiceInterface;
use Modules\StockMovement\Domain\Events\StockMovementConfirmed;
use Modules\StockMovement\Domain\Exceptions\StockMovementNotFoundException;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class ConfirmStockMovementService extends BaseService implements ConfirmStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    protected function handle(array $data): mixed
    {
        $id       = $data['id'];
        $movement = $this->movementRepository->find($id);

        if (! $movement) {
            throw new StockMovementNotFoundException($id);
        }

        $movement->confirm();

        $saved = $this->movementRepository->save($movement);
        $this->addEvent(new StockMovementConfirmed($saved));

        return $saved;
    }
}
