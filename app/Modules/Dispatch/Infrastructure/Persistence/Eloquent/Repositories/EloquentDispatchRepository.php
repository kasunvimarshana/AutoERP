<?php
declare(strict_types=1);
namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;
class EloquentDispatchRepository implements DispatchRepositoryInterface {
    public function __construct(private readonly DispatchModel $model, private readonly DispatchLineModel $lineModel) {}
    private function toEntity(DispatchModel $m): Dispatch {
        return new Dispatch($m->id,$m->tenant_id,$m->sales_order_id,$m->warehouse_id,$m->dispatch_number,$m->status,
            $m->carrier,$m->tracking_number,$m->shipping_cost?(float)$m->shipping_cost:null,
            $m->lines->toArray(),$m->shipped_at,$m->delivered_at,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?Dispatch { $m=$this->model->newQuery()->with('lines')->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->with('lines')->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data, array $lines): Dispatch {
        return DB::transaction(function() use ($data,$lines) {
            $m=$this->model->newQuery()->create($data);
            foreach($lines as $l) $this->lineModel->newQuery()->create(array_merge($l,['dispatch_id'=>$m->id]));
            return $this->findById($m->id);
        });
    }
    public function update(int $id, array $data): ?Dispatch { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->findById($id); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
