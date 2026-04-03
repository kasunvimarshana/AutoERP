<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptPutAway;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class PutAwayGoodsReceiptService extends BaseService implements PutAwayGoodsReceiptServiceInterface
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

        $receipt->putAway((int) $data['put_away_by']);

        $saved = $this->receiptRepository->save($receipt);
        $this->addEvent(new GoodsReceiptPutAway($saved, (int) $data['put_away_by']));

        return $saved;
    }
}
