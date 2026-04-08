<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptLineData;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineUpdated;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptLineNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;

class UpdateGoodsReceiptLineService extends BaseService implements UpdateGoodsReceiptLineServiceInterface
{
    public function __construct(private readonly GoodsReceiptLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): GoodsReceiptLine
    {
        $dto  = UpdateGoodsReceiptLineData::fromArray($data);
        $line = $this->lineRepository->find($dto->id);

        if (! $line) {
            throw new GoodsReceiptLineNotFoundException($dto->id);
        }

        $line->partialAccept($dto->quantityAccepted, $dto->quantityRejected);

        if ($dto->putawayLocationId !== null) {
            $line->setPutawayLocation($dto->putawayLocationId);
        }

        $saved = $this->lineRepository->save($line);
        $this->addEvent(new GoodsReceiptLineUpdated($saved->getId(), $saved->getGoodsReceiptId()));

        return $saved;
    }
}
