<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface {
    public function __construct(
        private readonly PurchaseOrderModel $model,
        private readonly PurchaseOrderLineModel $lineModel,
    ) {}
    private function toEntity(PurchaseOrderModel $m): PurchaseOrder {
        return new PurchaseOrder($m->id,$m->tenant_id,$m->supplier_id,$m->warehouse_id,$m->po_number,
            $m->status,(float)$m->total_amount,$m->currency,$m->expected_date?->format('Y-m-d'),$m->notes,
            $m->created_by,$m->lines->toArray(),$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?PurchaseOrder {
        $m=$this->model->newQuery()->with('lines')->find($id); return $m?$this->toEntity($m):null;
    }
    public function findByPoNumber(int $tenantId, string $poNumber): ?PurchaseOrder {
        $m=$this->model->newQuery()->with('lines')->where('tenant_id',$tenantId)->where('po_number',$poNumber)->first();
        return $m?$this->toEntity($m):null;
    }
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator {
        $q=$this->model->newQuery()->with('lines')->where('tenant_id',$tenantId);
        if(!empty($filters['status'])) $q->where('status',$filters['status']);
        return $q->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data, array $lines): PurchaseOrder {
        return DB::transaction(function() use ($data, $lines) {
            $m=$this->model->newQuery()->create($data);
            foreach($lines as $line) {
                $this->lineModel->newQuery()->create(array_merge($line,['purchase_order_id'=>$m->id]));
            }
            return $this->findById($m->id);
        });
    }
    public function update(int $id, array $data): ?PurchaseOrder {
        $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->findById($id);
    }
    public function updateStatus(int $id, string $status): bool {
        $m=$this->model->newQuery()->find($id); if(!$m)return false; $m->update(['status'=>$status]); return true;
    }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
