<?php
namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptInspected;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class InspectGoodsReceiptService implements InspectGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $repository) {}

    public function execute(int $grId, int $inspectedBy): GoodsReceipt
    {
        $gr = $this->repository->findById($grId);
        if (!$gr) throw new \DomainException("Goods receipt not found: {$grId}");
        $gr->inspect($inspectedBy);
        $gr = $this->repository->save($gr);
        Event::dispatch(new GoodsReceiptInspected($gr->tenantId, $gr->id));
        return $gr;
    }
}
