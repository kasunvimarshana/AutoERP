<?php
namespace Modules\CRM\Infrastructure\Repositories;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Infrastructure\Models\OpportunityModel;
class OpportunityRepository implements OpportunityRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return OpportunityModel::find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = OpportunityModel::query();
        if (!empty($filters['stage'])) $query->where('stage', $filters['stage']);
        if (!empty($filters['assigned_to'])) $query->where('assigned_to', $filters['assigned_to']);
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }
        return $query->latest()->paginate($perPage);
    }
    public function findByStage(string $stage): object
    {
        return OpportunityModel::where('stage', $stage)->get();
    }
    public function create(array $data): object
    {
        return OpportunityModel::create($data);
    }
    public function update(string $id, array $data): object
    {
        $opp = OpportunityModel::findOrFail($id);
        $opp->update($data);
        return $opp->fresh();
    }
    public function delete(string $id): bool
    {
        return OpportunityModel::findOrFail($id)->delete();
    }
}
