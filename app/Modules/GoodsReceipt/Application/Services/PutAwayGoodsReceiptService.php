<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Application\Services;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptPutAway;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
class PutAwayGoodsReceiptService implements PutAwayGoodsReceiptServiceInterface {
    public function __construct(private readonly GoodsReceiptRepositoryInterface $repo) {}
    public function execute(int $id, int $putAwayBy): GoodsReceipt {
        $gr=$this->repo->findById($id);
        if(!$gr) throw new NotFoundException("GoodsReceipt", $id);
        $gr->putAway($putAwayBy);
        $this->repo->update($id,['status'=>'put_away','put_away_by'=>$putAwayBy,'put_away_at'=>now()]);
        event(new GoodsReceiptPutAway($gr->getTenantId(),$id));
        return $this->repo->findById($id);
    }
}
