<?php
namespace Modules\Purchase\Infrastructure\Repositories;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Infrastructure\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Models\PurchaseOrderLineModel;
class PurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return PurchaseOrderModel::with('lines')->find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = PurchaseOrderModel::query();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['vendor_id'])) $query->where('vendor_id', $filters['vendor_id']);
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        $po = PurchaseOrderModel::create($data);
        foreach ($lines as $index => $line) {
            $line['purchase_order_id'] = $po->id;
            $line['sort_order'] = $index + 1;
            PurchaseOrderLineModel::create($line);
        }
        return $po->load('lines');
    }
    public function update(string $id, array $data): object
    {
        $po = PurchaseOrderModel::findOrFail($id);
        $lines = $data['lines'] ?? null;
        unset($data['lines']);
        $po->update($data);
        if ($lines !== null) {
            $po->lines()->delete();
            foreach ($lines as $index => $line) {
                $line['purchase_order_id'] = $po->id;
                $line['sort_order'] = $index + 1;
                PurchaseOrderLineModel::create($line);
            }
        }
        return $po->load('lines');
    }
    public function delete(string $id): bool
    {
        return PurchaseOrderModel::findOrFail($id)->delete();
    }
    public function nextNumber(string $tenantId): string
    {
        $count = PurchaseOrderModel::withTrashed()->where('tenant_id', $tenantId)->count();
        return 'PO-'.str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
