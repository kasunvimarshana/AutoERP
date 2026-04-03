<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\UpdateStockReturnLineServiceInterface;
use Modules\Returns\Application\DTOs\UpdateStockReturnLineData;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\StockReturnLineUpdated;
use Modules\Returns\Domain\Exceptions\StockReturnLineNotFoundException;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class UpdateStockReturnLineService extends BaseService implements UpdateStockReturnLineServiceInterface
{
    public function __construct(private readonly StockReturnLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): StockReturnLine
    {
        $dto  = UpdateStockReturnLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new StockReturnLineNotFoundException($dto->id);
        }

        $line->updateDetails($dto->notes, $dto->condition, $dto->disposition);

        if ($dto->quantityApproved !== null) {
            $line->approve($dto->quantityApproved);
        }

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new StockReturnLineUpdated($saved));

        return $saved;
    }
}
