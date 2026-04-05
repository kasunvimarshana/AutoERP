<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface {
    public function __construct(private readonly SalesOrderModel $model, private readonly SalesOrderLineModel $lineModel) {}
    private function toEntity(SalesOrderModel $m): SalesOrder {
        return new SalesOrder($m->id,$m->tenant_id,$m->customer_id,$m->warehouse_id,$m->so_number,$m->status,
            (float)$m->subtotal,(float)$m->tax_amount,(float)$m->total_amount,$m->currency,$m->notes,$m->created_by,$m->lines->toArray(),$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?SalesOrder { $m=$this->model->newQuery()->with('lines')->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator {
        $q=$this->model->newQuery()->with('lines')->where('tenant_id',$tenantId);
        if(!empty($filters['status'])) $q->where('status',$filters['status']);
        return $q->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data, array $lines): SalesOrder {
        return DB::transaction(function() use ($data,$lines) {
            $m=$this->model->newQuery()->create($data);
            foreach($lines as $l) $this->lineModel->newQuery()->create(array_merge($l,['sales_order_id'=>$m->id]));
            return $this->findById($m->id);
        });
    }
    public function update(int $id, array $data): ?SalesOrder { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->findById($id); }
    public function updateStatus(int $id, string $status): bool { $m=$this->model->newQuery()->find($id); if(!$m)return false; $m->update(['status'=>$status]); return true; }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
