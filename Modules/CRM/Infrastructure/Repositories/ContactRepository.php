<?php
namespace Modules\CRM\Infrastructure\Repositories;
use Modules\CRM\Domain\Contracts\ContactRepositoryInterface;
use Modules\CRM\Infrastructure\Models\ContactModel;
class ContactRepository implements ContactRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ContactModel::find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = ContactModel::query();
        if (!empty($filters['account_id'])) $query->where('account_id', $filters['account_id']);
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('last_name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('email', 'like', '%'.$filters['search'].'%');
            });
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return ContactModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $contact = ContactModel::findOrFail($id);
        $contact->update($data);
        return $contact->fresh();
    }
    public function delete(string $id): bool
    {
        return ContactModel::findOrFail($id)->delete();
    }
}
