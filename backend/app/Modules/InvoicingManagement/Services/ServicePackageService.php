<?php

namespace App\Modules\InvoicingManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InvoicingManagement\Events\ServicePackageCreated;
use App\Modules\InvoicingManagement\Repositories\ServicePackageRepository;
use Illuminate\Database\Eloquent\Model;

class ServicePackageService extends BaseService
{
    public function __construct(ServicePackageRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * After service package creation hook
     */
    protected function afterCreate(Model $package, array $data): void
    {
        event(new ServicePackageCreated($package));
    }

    /**
     * Get active packages
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Get packages by category
     */
    public function getByCategory(string $category)
    {
        return $this->repository->getByCategory($category);
    }

    /**
     * Activate package
     */
    public function activate(int $packageId): Model
    {
        return $this->update($packageId, ['is_active' => true]);
    }

    /**
     * Deactivate package
     */
    public function deactivate(int $packageId): Model
    {
        return $this->update($packageId, ['is_active' => false]);
    }

    /**
     * Update package price
     */
    public function updatePrice(int $packageId, float $price): Model
    {
        return $this->update($packageId, ['price' => $price]);
    }

    /**
     * Add item to package
     */
    public function addItem(int $packageId, array $itemData): Model
    {
        $package = $this->repository->findOrFail($packageId);
        $items = $package->items ?? [];
        $items[] = $itemData;
        
        return $this->update($packageId, ['items' => $items]);
    }

    /**
     * Remove item from package
     */
    public function removeItem(int $packageId, string $itemId): Model
    {
        $package = $this->repository->findOrFail($packageId);
        $items = $package->items ?? [];
        
        $items = array_filter($items, function ($item) use ($itemId) {
            return $item['id'] !== $itemId;
        });
        
        return $this->update($packageId, ['items' => array_values($items)]);
    }

    /**
     * Calculate total package value
     */
    public function calculateValue(int $packageId): float
    {
        $package = $this->repository->findOrFail($packageId);
        return $package->items->sum('price') ?? 0;
    }
}
