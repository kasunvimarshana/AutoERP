<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\ApproveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptApproved;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class ApproveGoodsReceiptService extends BaseService implements ApproveGoodsReceiptServiceInterface
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

        $receipt->approve((int) $data['approved_by']);

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptApproved($saved->getId(), $saved->getApprovedBy()));

        return $saved;
    }
}
