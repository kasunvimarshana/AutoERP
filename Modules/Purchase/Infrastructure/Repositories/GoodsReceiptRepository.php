<?php
namespace Modules\Purchase\Infrastructure\Repositories;
use Modules\Purchase\Domain\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Purchase\Infrastructure\Models\GoodsReceiptModel;
use Modules\Purchase\Infrastructure\Models\GoodsReceiptLineModel;
class GoodsReceiptRepository implements GoodsReceiptRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return GoodsReceiptModel::with('lines')->find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = GoodsReceiptModel::query();
        if (!empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        $grn = GoodsReceiptModel::create($data);
        foreach ($lines as $line) {
            $line['goods_receipt_id'] = $grn->id;
            GoodsReceiptLineModel::create($line);
        }
        return $grn->load('lines');
    }
}
