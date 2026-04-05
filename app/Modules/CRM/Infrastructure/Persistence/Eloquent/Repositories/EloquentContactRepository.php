<?php
declare(strict_types=1);
namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\CRM\Domain\Entities\Contact;
use Modules\CRM\Domain\RepositoryInterfaces\ContactRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\ContactModel;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function __construct(private readonly ContactModel $model) {}

    public function findById(int $id): ?Contact
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId): array
    {
        return $this->model->newQuery()->where('tenant_id', $tenantId)->orderBy('first_name')
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): Contact
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?Contact
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ContactModel $m): Contact
    {
        return new Contact(
            $m->id, $m->tenant_id, $m->type,
            $m->first_name, $m->last_name, $m->company, $m->job_title,
            $m->email, $m->phone, $m->mobile, $m->address,
            $m->owner_id, $m->customer_id, $m->supplier_id,
            (bool) $m->is_active, $m->created_at, $m->updated_at,
        );
    }
}
