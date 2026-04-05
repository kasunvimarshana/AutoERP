<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;
class EloquentGoodsReceiptRepository implements GoodsReceiptRepositoryInterface {
    public function __construct(private readonly GoodsReceiptModel $model, private readonly GoodsReceiptLineModel $lineModel) {}
    private function toEntity(GoodsReceiptModel $m): GoodsReceipt {
        return new GoodsReceipt($m->id,$m->tenant_id,$m->purchase_order_id,$m->warehouse_id,$m->gr_number,
            $m->status,$m->notes,$m->received_by,$m->inspected_by,$m->inspected_at,$m->put_away_by,
            $m->put_away_at,$m->lines->toArray(),$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?GoodsReceipt { $m=$this->model->newQuery()->with('lines')->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->with('lines')->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data, array $lines): GoodsReceipt {
        return DB::transaction(function() use ($data,$lines) {
            $m=$this->model->newQuery()->create($data);
            foreach($lines as $l) $this->lineModel->newQuery()->create(array_merge($l,['goods_receipt_id'=>$m->id]));
            return $this->findById($m->id);
        });
    }
    public function update(int $id, array $data): ?GoodsReceipt { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->findById($id); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
