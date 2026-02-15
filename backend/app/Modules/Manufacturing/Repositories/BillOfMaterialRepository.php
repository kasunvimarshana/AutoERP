<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use Illuminate\Database\Eloquent\Collection;

class BillOfMaterialRepository extends BaseRepository implements BillOfMaterialRepositoryInterface
{
    /**
     * BillOfMaterialRepository constructor.
     *
     * @param BillOfMaterial $model
     */
    public function __construct(BillOfMaterial $model)
    {
        parent::__construct($model);
    }

    /**
     * Find BOM by number
     *
     * @param string $bomNumber
     * @return BillOfMaterial|null
     */
    public function findByNumber(string $bomNumber): ?BillOfMaterial
    {
        return $this->model->where('bom_number', $bomNumber)->first();
    }

    /**
     * Get BOMs by product
     *
     * @param int $productId
     * @return Collection
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('is_default', 'desc')
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get active BOMs
     *
     * @return Collection
     */
    public function getActiveBOMs(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get default BOM for a product
     *
     * @param int $productId
     * @return BillOfMaterial|null
     */
    public function getDefaultBOM(int $productId): ?BillOfMaterial
    {
        return $this->model->where('product_id', $productId)
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get valid BOMs for a date
     *
     * @param string|null $date
     * @return Collection
     */
    public function getValidBOMs(?string $date = null): Collection
    {
        return $this->model->valid($date)->get();
    }

    /**
     * Search BOMs
     *
     * @param array $filters
     * @return Collection
     */
    public function search(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('bom_number', 'like', "%{$filters['search']}%")
                  ->orWhere('name', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }

        if (isset($filters['version'])) {
            $query->where('version', $filters['version']);
        }

        return $query->with(['product', 'unit', 'items.product'])->get();
    }

    /**
     * Get BOM with items
     *
     * @param int $id
     * @return BillOfMaterial|null
     */
    public function getWithItems(int $id): ?BillOfMaterial
    {
        return $this->model->with([
            'product',
            'unit',
            'items.product',
            'items.unit',
        ])->find($id);
    }

    /**
     * Get BOMs by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }
}
