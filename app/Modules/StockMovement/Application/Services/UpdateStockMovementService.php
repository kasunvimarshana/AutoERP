<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\StockMovement\Application\Contracts\UpdateStockMovementServiceInterface;
use Modules\StockMovement\Application\DTOs\UpdateStockMovementData;
use Modules\StockMovement\Domain\Events\StockMovementUpdated;
use Modules\StockMovement\Domain\Exceptions\StockMovementNotFoundException;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class UpdateStockMovementService extends BaseService implements UpdateStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    protected function handle(array $data): mixed
    {
        $dto      = UpdateStockMovementData::fromArray($data);
        $movement = $this->movementRepository->find($dto->id);

        if (! $movement) {
            throw new StockMovementNotFoundException($dto->id);
        }

        $movement->updateDetails($dto->notes, $dto->metadata, $dto->status);

        $saved = $this->movementRepository->save($movement);
        $this->addEvent(new StockMovementUpdated($saved));

        return $saved;
    }
}
