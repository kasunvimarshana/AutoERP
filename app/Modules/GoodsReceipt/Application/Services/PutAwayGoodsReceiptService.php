<?php
namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptPutAway;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class PutAwayGoodsReceiptService implements PutAwayGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $repository) {}

    public function execute(int $grId, int $putAwayBy): GoodsReceipt
    {
        $gr = $this->repository->findById($grId);
        if (!$gr) throw new \DomainException("Goods receipt not found: {$grId}");
        $gr->putAway($putAwayBy);
        $gr = $this->repository->save($gr);
        Event::dispatch(new GoodsReceiptPutAway($gr->tenantId, $gr->id));
        return $gr;
    }
}
