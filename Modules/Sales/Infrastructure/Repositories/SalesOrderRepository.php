<?php
namespace Modules\Sales\Infrastructure\Repositories;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Infrastructure\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Models\SalesOrderLineModel;
class SalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return SalesOrderModel::with('lines')->find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = SalesOrderModel::query();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        $order = SalesOrderModel::create($data);
        foreach ($lines as $index => $line) {
            $line['sales_order_id'] = $order->id;
            $line['sort_order'] = $index + 1;
            SalesOrderLineModel::create($line);
        }
        return $order->load('lines');
    }
    public function update(string $id, array $data): object
    {
        $order = SalesOrderModel::findOrFail($id);
        $lines = $data['lines'] ?? null;
        unset($data['lines']);
        $order->update($data);
        if ($lines !== null) {
            $order->lines()->delete();
            foreach ($lines as $index => $line) {
                $line['sales_order_id'] = $order->id;
                $line['sort_order'] = $index + 1;
                SalesOrderLineModel::create($line);
            }
        }
        return $order->load('lines');
    }
    public function delete(string $id): bool
    {
        return SalesOrderModel::findOrFail($id)->delete();
    }
    public function nextNumber(string $tenantId): string
    {
        $count = SalesOrderModel::withTrashed()->where('tenant_id', $tenantId)->count();
        return 'SO-'.str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
