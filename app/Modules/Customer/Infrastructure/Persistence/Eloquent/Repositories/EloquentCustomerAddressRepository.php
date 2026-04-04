<?php
namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;

class EloquentCustomerAddressRepository extends EloquentRepository implements CustomerAddressRepositoryInterface
{
    public function __construct(CustomerAddressModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?CustomerAddress
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCustomer(int $customerId): array
    {
        return $this->model->where('customer_id', $customerId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): CustomerAddress
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        $model = $this->model->findOrFail($address->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(CustomerAddress $address): bool
    {
        $model = $this->model->findOrFail($address->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): CustomerAddress
    {
        return new CustomerAddress(
            id: $model->id,
            customerId: $model->customer_id,
            addressType: $model->address_type,
            street: $model->street,
            city: $model->city,
            state: $model->state,
            country: $model->country,
            postalCode: $model->postal_code,
            isDefault: (bool) $model->is_default,
        );
    }
}
