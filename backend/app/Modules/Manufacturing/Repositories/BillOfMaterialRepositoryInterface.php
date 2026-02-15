<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Repositories;

use App\Modules\Manufacturing\Models\BillOfMaterial;
use Illuminate\Database\Eloquent\Collection;

interface BillOfMaterialRepositoryInterface
{
    /**
     * Find BOM by number
     *
     * @param string $bomNumber
     * @return BillOfMaterial|null
     */
    public function findByNumber(string $bomNumber): ?BillOfMaterial;

    /**
     * Get BOMs by product
     *
     * @param int $productId
     * @return Collection
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get active BOMs
     *
     * @return Collection
     */
    public function getActiveBOMs(): Collection;

    /**
     * Get default BOM for a product
     *
     * @param int $productId
     * @return BillOfMaterial|null
     */
    public function getDefaultBOM(int $productId): ?BillOfMaterial;

    /**
     * Get valid BOMs for a date
     *
     * @param string|null $date
     * @return Collection
     */
    public function getValidBOMs(?string $date = null): Collection;

    /**
     * Search BOMs
     *
     * @param array $filters
     * @return Collection
     */
    public function search(array $filters = []): Collection;

    /**
     * Get BOM with items
     *
     * @param int $id
     * @return BillOfMaterial|null
     */
    public function getWithItems(int $id): ?BillOfMaterial;

    /**
     * Get BOMs by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;
}
