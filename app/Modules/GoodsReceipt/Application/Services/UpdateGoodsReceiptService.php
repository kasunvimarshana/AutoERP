<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptUpdated;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class UpdateGoodsReceiptService extends BaseService implements UpdateGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    protected function handle(array $data): GoodsReceipt
    {
        $dto     = UpdateGoodsReceiptData::fromArray($data);
        $receipt = $this->receiptRepository->find($dto->id);

        if (! $receipt) {
            throw new GoodsReceiptNotFoundException($dto->id);
        }

        $receipt->updateDetails(
            $dto->notes,
            $dto->metadata,
            $dto->warehouseId,
            $dto->receivedDate ? new \DateTimeImmutable($dto->receivedDate) : null,
        );

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptUpdated($saved->getId(), $saved->getTenantId()));

        return $saved;
    }
}
