<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\SalesOrder\Application\Contracts\UpdateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\DTOs\UpdateSalesOrderLineData;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;
use Modules\SalesOrder\Domain\Events\SalesOrderLineUpdated;
use Modules\SalesOrder\Domain\Exceptions\SalesOrderLineNotFoundException;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;

class UpdateSalesOrderLineService extends BaseService implements UpdateSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): SalesOrderLine
    {
        $dto  = UpdateSalesOrderLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new SalesOrderLineNotFoundException($dto->id);
        }

        $line->updateDetails(
            $dto->quantity,
            $dto->unitPrice,
            $dto->taxRate,
            $dto->discountAmount,
            $dto->totalAmount,
            $dto->warehouseLocationId,
            $dto->batchNumber,
            $dto->serialNumber,
            $dto->description,
            $dto->notes,
            $dto->metadata,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new SalesOrderLineUpdated($saved->getId(), $saved->getSalesOrderId()));

        return $saved;
    }
}
