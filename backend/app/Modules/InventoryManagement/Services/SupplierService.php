<?php

namespace App\Modules\InventoryManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InventoryManagement\Events\SupplierCreated;
use App\Modules\InventoryManagement\Repositories\SupplierRepository;
use Illuminate\Database\Eloquent\Model;

class SupplierService extends BaseService
{
    public function __construct(SupplierRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After supplier creation hook
     */
    protected function afterCreate(Model $supplier, array $data): void
    {
        event(new SupplierCreated($supplier));
    }

    /**
     * Search suppliers
     */
    public function search(string $query)
    {
        return $this->repository->search($query);
    }

    /**
     * Get active suppliers
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Get suppliers by rating
     */
    public function getByRating(float $minRating)
    {
        return $this->repository->getByRating($minRating);
    }

    /**
     * Update supplier rating
     */
    public function updateRating(int $supplierId, float $rating): Model
    {
        return $this->update($supplierId, ['rating' => $rating]);
    }

    /**
     * Mark supplier as preferred
     */
    public function markAsPreferred(int $supplierId): Model
    {
        return $this->update($supplierId, ['is_preferred' => true]);
    }

    /**
     * Remove preferred status
     */
    public function removePreferred(int $supplierId): Model
    {
        return $this->update($supplierId, ['is_preferred' => false]);
    }

    /**
     * Get preferred suppliers
     */
    public function getPreferred()
    {
        return $this->repository->getPreferred();
    }

    /**
     * Deactivate supplier
     */
    public function deactivate(int $supplierId): Model
    {
        return $this->update($supplierId, ['status' => 'inactive']);
    }

    /**
     * Activate supplier
     */
    public function activate(int $supplierId): Model
    {
        return $this->update($supplierId, ['status' => 'active']);
    }
}
