<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Stock Movement Repository - immutable audit trail
 */
class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    protected array $filterableColumns = ['tenant_id', 'product_id', 'warehouse_id', 'movement_type'];
    protected array $sortableColumns = ['created_at'];

    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    public function recordMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    public function getMovementsForProduct(string $productId, array $params = []): Collection|LengthAwarePaginator
    {
        $params['product_id'] = $productId;
        return $this->getAll($params);
    }
}
