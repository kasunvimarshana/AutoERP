<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptLineData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineCreated;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;

class CreateGoodsReceiptLineService extends BaseService implements CreateGoodsReceiptLineServiceInterface
{
    public function __construct(private readonly GoodsReceiptLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): GoodsReceiptLine
    {
        $dto = GoodsReceiptLineData::fromArray($data);

        $line = new GoodsReceiptLine(
            tenantId:            $dto->tenantId,
            goodsReceiptId:      $dto->goodsReceiptId,
            lineNumber:          $dto->lineNumber,
            productId:           $dto->productId,
            quantityReceived:    $dto->quantityReceived,
            purchaseOrderLineId: $dto->purchaseOrderLineId,
            variationId:         $dto->variationId,
            batchId:             $dto->batchId,
            serialNumber:        $dto->serialNumber,
            uomId:               $dto->uomId,
            quantityExpected:    $dto->quantityExpected,
            quantityAccepted:    $dto->quantityAccepted,
            quantityRejected:    $dto->quantityRejected,
            unitCost:            $dto->unitCost,
            condition:           $dto->condition,
            notes:               $dto->notes,
            metadata:            $dto->metadata ? new Metadata($dto->metadata) : null,
            status:              $dto->status,
            putawayLocationId:   $dto->putawayLocationId,
        );

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new GoodsReceiptLineCreated($saved->getId(), $saved->getGoodsReceiptId()));

        return $saved;
    }
}
