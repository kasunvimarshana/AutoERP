<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\UpdateStockReturnServiceInterface;
use Modules\Returns\Application\DTOs\UpdateStockReturnData;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\Events\StockReturnUpdated;
use Modules\Returns\Domain\Exceptions\StockReturnNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;

class UpdateStockReturnService extends BaseService implements UpdateStockReturnServiceInterface
{
    public function __construct(private readonly StockReturnRepositoryInterface $returnRepository)
    {
        parent::__construct($returnRepository);
    }

    protected function handle(array $data): StockReturn
    {
        $dto    = UpdateStockReturnData::fromArray($data);
        $return = $this->returnRepository->find($dto->id);

        if (! $return) {
            throw new StockReturnNotFoundException($dto->id);
        }

        $return->updateDetails($dto->notes, $dto->metadata, $dto->returnReason);

        $saved = $this->returnRepository->save($return);
        $this->addEvent(new StockReturnUpdated($saved));

        return $saved;
    }
}
