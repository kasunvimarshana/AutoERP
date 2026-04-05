<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Application\Services;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptInspected;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
class InspectGoodsReceiptService implements InspectGoodsReceiptServiceInterface {
    public function __construct(private readonly GoodsReceiptRepositoryInterface $repo) {}
    public function execute(int $id, int $inspectedBy): GoodsReceipt {
        $gr=$this->repo->findById($id);
        if(!$gr) throw new NotFoundException("GoodsReceipt", $id);
        $gr->inspect($inspectedBy);
        $this->repo->update($id,['status'=>'inspected','inspected_by'=>$inspectedBy,'inspected_at'=>now()]);
        event(new GoodsReceiptInspected($gr->getTenantId(),$id));
        return $this->repo->findById($id);
    }
}
