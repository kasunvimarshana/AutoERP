<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\SalaryStructureRepositoryInterface;
use Modules\HR\Infrastructure\Models\SalaryStructureLineModel;
use Modules\HR\Infrastructure\Models\SalaryStructureModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class SalaryStructureRepository extends BaseEloquentRepository implements SalaryStructureRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SalaryStructureModel());
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return SalaryStructureModel::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function findWithLines(string $id): ?object
    {
        return SalaryStructureModel::with(['lines.component'])
            ->find($id);
    }

    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $structure = SalaryStructureModel::create($data);

        foreach ($lines as $i => $line) {
            SalaryStructureLineModel::create([
                'structure_id'    => $structure->id,
                'component_id'    => $line['component_id'],
                'sequence'        => $line['sequence'] ?? (($i + 1) * 10),
                'override_amount' => isset($line['override_amount'])
                    ? bcadd($line['override_amount'], '0.00000000', 8)
                    : null,
            ]);
        }

        return $this->findWithLines($structure->id);
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = SalaryStructureModel::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }
}
