<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Application\DTOs\PurchaseOrderLineData;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineCreated;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;

class CreatePurchaseOrderLineService extends BaseService implements CreatePurchaseOrderLineServiceInterface
{
    public function __construct(private readonly PurchaseOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): PurchaseOrderLine
    {
        $dto = PurchaseOrderLineData::fromArray($data);

        $line = new PurchaseOrderLine(
            tenantId:        $dto->tenantId,
            purchaseOrderId: $dto->purchaseOrderId,
            lineNumber:      $dto->lineNumber,
            productId:       $dto->productId,
            quantityOrdered: $dto->quantityOrdered,
            unitPrice:       $dto->unitPrice,
            variationId:     $dto->variationId,
            description:     $dto->description,
            uomId:           $dto->uomId,
            discountPercent: $dto->discountPercent,
            taxPercent:      $dto->taxPercent,
            lineTotal:       $dto->lineTotal,
            expectedDate:    $dto->expectedDate,
            notes:           $dto->notes,
            metadata:        $dto->metadata,
            status:          $dto->status,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new PurchaseOrderLineCreated($saved->getId(), $saved->getPurchaseOrderId()));

        return $saved;
    }
}
