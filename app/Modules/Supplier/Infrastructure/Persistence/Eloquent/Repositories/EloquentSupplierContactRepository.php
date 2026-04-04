<?php
namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;

class EloquentSupplierContactRepository extends EloquentRepository implements SupplierContactRepositoryInterface
{
    public function __construct(SupplierContactModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?SupplierContact
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySupplier(int $supplierId): array
    {
        return $this->model->where('supplier_id', $supplierId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): SupplierContact
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(SupplierContact $contact, array $data): SupplierContact
    {
        $model = $this->model->findOrFail($contact->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(SupplierContact $contact): bool
    {
        $model = $this->model->findOrFail($contact->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): SupplierContact
    {
        return new SupplierContact(
            id: $model->id,
            supplierId: $model->supplier_id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            position: $model->position,
            isPrimary: (bool) $model->is_primary,
        );
    }
}
