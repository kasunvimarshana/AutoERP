<?php
declare(strict_types=1);
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\GS1\Domain\Entities\Gs1Label;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1LabelRepositoryInterface;
use Modules\GS1\Infrastructure\Persistence\Eloquent\Models\Gs1LabelModel;
class EloquentGs1LabelRepository implements Gs1LabelRepositoryInterface {
    public function __construct(private readonly Gs1LabelModel $model) {}
    private function toEntity(Gs1LabelModel $m): Gs1Label {
        return new Gs1Label($m->id,$m->tenant_id,$m->product_id,$m->gs1_type,$m->gs1_value,
            $m->batch_number,$m->lot_number,$m->serial_number,$m->expiry_date,
            $m->net_weight?(float)$m->net_weight:null,$m->country_of_origin,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?Gs1Label { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByValue(string $value): ?Gs1Label { $m=$this->model->newQuery()->where('gs1_value',$value)->first(); return $m?$this->toEntity($m):null; }
    public function findByProduct(int $tenantId, int $productId): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->where('product_id',$productId)->paginate(15);
    }
    public function create(array $data): Gs1Label { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?Gs1Label { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
