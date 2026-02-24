<?php
namespace Modules\Inventory\Infrastructure\Repositories;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\StockMovementModel;
use Illuminate\Support\Str;
class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return StockMovementModel::find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = StockMovementModel::query();
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['product_id'])) $query->where('product_id', $filters['product_id']);
        if (!empty($filters['location_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_location_id', $filters['location_id'])
                  ->orWhere('to_location_id', $filters['location_id']);
            });
        }
        return $query->orderBy('posted_at', 'desc')->paginate($perPage);
    }
    public function create(array $data): object
    {
        if (empty($data['id'])) {
            $data['id'] = (string) Str::uuid();
        }
        return StockMovementModel::create($data);
    }
}
