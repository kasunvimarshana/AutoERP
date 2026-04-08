<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Returns\Application\Contracts\CreateStockReturnLineServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnLineData;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\StockReturnLineCreated;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;

class CreateStockReturnLineService extends BaseService implements CreateStockReturnLineServiceInterface
{
    public function __construct(private readonly StockReturnLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): StockReturnLine
    {
        $dto = StockReturnLineData::fromArray($data);

        $line = new StockReturnLine(
            tenantId:          $dto->tenantId,
            stockReturnId:     $dto->stockReturnId,
            productId:         $dto->productId,
            quantityRequested: $dto->quantityRequested,
            variationId:       $dto->variationId,
            batchId:           $dto->batchId,
            serialNumberId:    $dto->serialNumberId,
            uomId:             $dto->uomId,
            quantityApproved:  $dto->quantityApproved,
            unitPrice:         $dto->unitPrice,
            unitCost:          $dto->unitCost,
            condition:         $dto->condition,
            disposition:       $dto->disposition,
            notes:             $dto->notes,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new StockReturnLineCreated($saved));

        return $saved;
    }
}
