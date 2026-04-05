<?php
declare(strict_types=1);
namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
class EloquentCustomerRepository implements CustomerRepositoryInterface {
    public function __construct(private readonly CustomerModel $model) {}
    private function toEntity(CustomerModel $m): Customer {
        return new Customer($m->id, $m->tenant_id, $m->name, $m->code, $m->email,
            $m->phone, $m->address, (bool)$m->is_active, $m->metadata, $m->created_at, $m->updated_at);
    }
    public function findById(int $id): ?Customer {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }
    public function findByCode(int $tenantId, string $code): ?Customer {
        $m = $this->model->newQuery()->where('tenant_id',$tenantId)->where('code',$code)->first();
        return $m ? $this->toEntity($m) : null;
    }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)
            ->paginate($perPage, ['*'], 'page', $page)->through(fn($m) => $this->toEntity($m));
    }
    public function create(array $data): Customer {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }
    public function update(int $id, array $data): ?Customer {
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
