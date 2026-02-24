<?php

namespace Modules\Purchase\Infrastructure\Repositories;

use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Infrastructure\Models\PurchaseRequisitionModel;
use Modules\Purchase\Infrastructure\Models\PurchaseRequisitionLineModel;

class PurchaseRequisitionRepository implements PurchaseRequisitionRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return PurchaseRequisitionModel::with('lines')->find($id);
    }

    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = PurchaseRequisitionModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }

        if (! empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        return $query->with('lines')->latest()->paginate($perPage);
    }

    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $requisition = PurchaseRequisitionModel::create($data);

        foreach ($lines as $index => $line) {
            $line['requisition_id'] = $requisition->id;
            $line['sort_order']     = $index + 1;
            PurchaseRequisitionLineModel::create($line);
        }

        return $requisition->load('lines');
    }

    public function update(string $id, array $data): object
    {
        $requisition = PurchaseRequisitionModel::findOrFail($id);
        $lines       = $data['lines'] ?? null;
        unset($data['lines']);

        $requisition->update($data);

        if ($lines !== null) {
            $requisition->lines()->delete();
            foreach ($lines as $index => $line) {
                $line['requisition_id'] = $requisition->id;
                $line['sort_order']     = $index + 1;
                PurchaseRequisitionLineModel::create($line);
            }
        }

        return $requisition->load('lines');
    }

    public function delete(string $id): bool
    {
        return PurchaseRequisitionModel::findOrFail($id)->delete();
    }

    public function nextNumber(string $tenantId): string
    {
        $count = PurchaseRequisitionModel::withTrashed()
            ->where('tenant_id', $tenantId)
            ->count();

        return 'PR-' . date('Y') . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
