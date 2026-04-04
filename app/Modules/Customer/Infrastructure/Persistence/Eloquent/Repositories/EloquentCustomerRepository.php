<?php
namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public function __construct(CustomerModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Customer
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCode(int $tenantId, string $code): ?Customer
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->paginate($perPage);
    }

    public function create(array $data): Customer
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $model = $this->model->findOrFail($customer->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(Customer $customer): bool
    {
        $model = $this->model->findOrFail($customer->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): Customer
    {
        return new Customer(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            status: $model->status,
            email: $model->email,
            phone: $model->phone,
            taxNumber: $model->tax_number,
            currency: $model->currency,
            creditLimit: $model->credit_limit !== null ? (float) $model->credit_limit : null,
            notes: $model->notes,
        );
    }
}
