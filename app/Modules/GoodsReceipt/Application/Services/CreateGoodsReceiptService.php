<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptCreated;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class CreateGoodsReceiptService extends BaseService implements CreateGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    protected function handle(array $data): GoodsReceipt
    {
        $dto = GoodsReceiptData::fromArray($data);

        $receipt = new GoodsReceipt(
            tenantId:        $dto->tenantId,
            referenceNumber: $dto->referenceNumber,
            supplierId:      $dto->supplierId,
            purchaseOrderId: $dto->purchaseOrderId,
            warehouseId:     $dto->warehouseId,
            receivedDate:    $dto->receivedDate ? new \DateTimeImmutable($dto->receivedDate) : null,
            currency:        $dto->currency,
            notes:           $dto->notes,
            metadata:        $dto->metadata ? new Metadata($dto->metadata) : null,
            status:          $dto->status,
            receivedBy:      $dto->receivedBy,
        );

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptCreated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
