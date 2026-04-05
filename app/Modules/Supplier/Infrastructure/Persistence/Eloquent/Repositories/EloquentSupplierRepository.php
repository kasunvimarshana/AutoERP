<?php
declare(strict_types=1);
namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
class EloquentSupplierRepository implements SupplierRepositoryInterface {
    public function __construct(private readonly SupplierModel $model) {}
    private function toEntity(SupplierModel $m): Supplier {
        return new Supplier($m->id, $m->tenant_id, $m->name, $m->code, $m->email,
            $m->phone, $m->address, (bool)$m->is_active, $m->metadata, $m->created_at, $m->updated_at);
    }
    public function findById(int $id): ?Supplier {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }
    public function findByCode(int $tenantId, string $code): ?Supplier {
        $m = $this->model->newQuery()->where('tenant_id',$tenantId)->where('code',$code)->first();
        return $m ? $this->toEntity($m) : null;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)
            ->paginate($perPage, ['*'], 'page', $page)->through(fn($m) => $this->toEntity($m));
    }
    public function create(array $data): Supplier {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }
    public function update(int $id, array $data): ?Supplier {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }
    public function delete(int $id): bool {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
