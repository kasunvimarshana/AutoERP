<?php
namespace Modules\CRM\Infrastructure\Repositories;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Infrastructure\Models\LeadModel;
class LeadRepository implements LeadRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LeadModel::find($id);
    }
    public function findByEmail(string $email): ?object
    {
        return LeadModel::where('email', $email)->first();
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = LeadModel::query();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['source'])) $query->where('source', $filters['source']);
        if (!empty($filters['assigned_to'])) $query->where('assigned_to', $filters['assigned_to']);
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('email', 'like', '%'.$filters['search'].'%')
                  ->orWhere('company', 'like', '%'.$filters['search'].'%');
            });
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        return LeadModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $lead = LeadModel::findOrFail($id);
        $lead->update($data);
        return $lead->fresh();
    }
    public function delete(string $id): bool
    {
        return LeadModel::findOrFail($id)->delete();
    }
}
