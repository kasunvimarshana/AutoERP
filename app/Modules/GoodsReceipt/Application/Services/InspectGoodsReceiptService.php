<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptInspected;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class InspectGoodsReceiptService extends BaseService implements InspectGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    protected function handle(array $data): GoodsReceipt
    {
        $id      = (int) $data['id'];
        $receipt = $this->receiptRepository->find($id);

        if (! $receipt) {
            throw new GoodsReceiptNotFoundException($id);
        }

        $receipt->inspect((int) $data['inspected_by']);

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptInspected($saved, (int) $data['inspected_by']));

        return $saved;
    }
}
