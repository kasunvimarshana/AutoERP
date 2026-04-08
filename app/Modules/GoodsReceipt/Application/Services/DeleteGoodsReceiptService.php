<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptDeleted;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class DeleteGoodsReceiptService extends BaseService implements DeleteGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    protected function handle(array $data): bool
    {
        $id      = $data['id'];
        $receipt = $this->receiptRepository->find($id);

        if (! $receipt) {
            throw new GoodsReceiptNotFoundException($id);
        }

        $this->addEvent(new GoodsReceiptDeleted($receipt->getId()));

        return $this->receiptRepository->delete($id);
    }
}
