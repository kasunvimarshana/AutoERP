<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\SalesOrder\Application\Contracts\CreateSalesOrderLineServiceInterface;
use Modules\SalesOrder\Application\DTOs\SalesOrderLineData;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;
use Modules\SalesOrder\Domain\Events\SalesOrderLineCreated;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;

class CreateSalesOrderLineService extends BaseService implements CreateSalesOrderLineServiceInterface
{
    public function __construct(private readonly SalesOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): SalesOrderLine
    {
        $dto = SalesOrderLineData::fromArray($data);

        $line = new SalesOrderLine(
            tenantId:           $dto->tenantId,
            salesOrderId:       $dto->salesOrderId,
            productId:          $dto->productId,
            quantity:           $dto->quantity,
            unitPrice:          $dto->unitPrice,
            productVariantId:   $dto->productVariantId,
            description:        $dto->description,
            taxRate:            $dto->taxRate,
            discountAmount:     $dto->discountAmount,
            totalAmount:        $dto->totalAmount,
            unitOfMeasure:      $dto->unitOfMeasure,
            status:             $dto->status,
            warehouseLocationId: $dto->warehouseLocationId,
            batchNumber:        $dto->batchNumber,
            serialNumber:       $dto->serialNumber,
            notes:              $dto->notes,
            metadata:           $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new SalesOrderLineCreated($saved->getId(), $saved->getSalesOrderId()));

        return $saved;
    }
}
