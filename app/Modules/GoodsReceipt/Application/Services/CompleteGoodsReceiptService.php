<?php
namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\GoodsReceipt\Application\Contracts\CompleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptCompleted;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class CompleteGoodsReceiptService implements CompleteGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $repository) {}

    public function execute(int $grId): GoodsReceipt
    {
        $gr = $this->repository->findById($grId);
        if (!$gr) throw new \DomainException("Goods receipt not found: {$grId}");
        $gr->complete();
        $gr = $this->repository->save($gr);
        Event::dispatch(new GoodsReceiptCompleted($gr->tenantId, $gr->id));
        return $gr;
    }
}
