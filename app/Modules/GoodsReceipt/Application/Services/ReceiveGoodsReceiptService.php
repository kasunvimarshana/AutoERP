<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\ReceiveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptReceived;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class ReceiveGoodsReceiptService extends BaseService implements ReceiveGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    protected function handle(array $data): GoodsReceipt
    {
        $id      = $data['id'];
        $receipt = $this->receiptRepository->find($id);

        if (! $receipt) {
            throw new GoodsReceiptNotFoundException($id);
        }

        $receipt->receive((int) $data['received_by']);

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptReceived($saved->getId(), $saved->getReceivedBy()));

        return $saved;
    }
}
