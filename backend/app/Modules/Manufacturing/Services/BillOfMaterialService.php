<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepositoryInterface;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\BOMItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BillOfMaterialService
 * 
 * Handles business logic for Bill of Materials management
 */
class BillOfMaterialService extends BaseService
{
    /**
     * BillOfMaterialService constructor.
     *
     * @param BillOfMaterialRepositoryInterface $repository
     */
    public function __construct(BillOfMaterialRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new BOM with items
     *
     * @param array $data
     * @return BillOfMaterial
     * @throws ServiceException
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Check if BOM number already exists
            if (isset($data['bom_number'])) {
                $existing = $this->repository->findByNumber($data['bom_number']);
                if ($existing) {
                    throw new ServiceException('BOM number already exists');
                }
            } else {
                // Auto-generate BOM number if not provided
                $data['bom_number'] = $this->generateBOMNumber();
            }

            // If this is set as default, unset other defaults for this product
            if (isset($data['is_default']) && $data['is_default']) {
                $this->unsetDefaultBOMs($data['product_id']);
            }

            // Extract items data
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Create the BOM
            $bom = $this->repository->create($data);

            // Create BOM items if provided
            if (!empty($items)) {
                $this->createBOMItems($bom, $items);
            }

            // Update estimated cost
            $this->updateEstimatedCost($bom);

            DB::commit();
            
            return $this->repository->getWithItems($bom->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create BOM: ' . $e->getMessage());
            throw new ServiceException('Failed to create BOM: ' . $e->getMessage());
        }
    }

    /**
     * Update BOM with items
     *
     * @param int $id
     * @param array $data
     * @return BillOfMaterial
     * @throws ServiceException
     */
    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $bom = $this->repository->findById($id);
            
            if (!$bom) {
                throw new ServiceException('BOM not found');
            }

            // Check BOM number uniqueness if changed
            if (isset($data['bom_number']) && $data['bom_number'] !== $bom->bom_number) {
                $existing = $this->repository->findByNumber($data['bom_number']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('BOM number already exists');
                }
            }

            // If this is set as default, unset other defaults for this product
            if (isset($data['is_default']) && $data['is_default']) {
                $this->unsetDefaultBOMs($bom->product_id, $id);
            }

            // Extract items data
            $items = $data['items'] ?? null;
            unset($data['items']);

            // Update the BOM
            $this->repository->update($id, $data);

            // Update BOM items if provided
            if ($items !== null) {
                $this->updateBOMItems($bom, $items);
            }

            // Recalculate estimated cost
            $this->updateEstimatedCost($bom);

            DB::commit();
            
            return $this->repository->getWithItems($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update BOM: ' . $e->getMessage());
            throw new ServiceException('Failed to update BOM: ' . $e->getMessage());
        }
    }

    /**
     * Activate BOM
     *
     * @param int $id
     * @return BillOfMaterial
     * @throws ServiceException
     */
    public function activate(int $id)
    {
        DB::beginTransaction();
        
        try {
            $bom = $this->repository->findById($id);
            
            if (!$bom) {
                throw new ServiceException('BOM not found');
            }

            if ($bom->status === 'active') {
                throw new ServiceException('BOM is already active');
            }

            // Validate BOM has items
            if ($bom->items->isEmpty()) {
                throw new ServiceException('Cannot activate BOM without items');
            }

            $this->repository->update($id, ['status' => 'active']);

            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate BOM: ' . $e->getMessage());
            throw new ServiceException('Failed to activate BOM: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate BOM
     *
     * @param int $id
     * @return BillOfMaterial
     * @throws ServiceException
     */
    public function deactivate(int $id)
    {
        return $this->update($id, ['status' => 'inactive']);
    }

    /**
     * Get BOM by product
     *
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByProduct(int $productId)
    {
        return $this->repository->getByProduct($productId);
    }

    /**
     * Get default BOM for a product
     *
     * @param int $productId
     * @return BillOfMaterial|null
     */
    public function getDefaultBOM(int $productId)
    {
        return $this->repository->getDefaultBOM($productId);
    }

    /**
     * Search BOMs
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(array $filters = [])
    {
        return $this->repository->search($filters);
    }

    /**
     * Calculate material requirements
     *
     * @param int $bomId
     * @param float $quantity
     * @return array
     * @throws ServiceException
     */
    public function calculateMaterialRequirements(int $bomId, float $quantity = 1): array
    {
        $bom = $this->repository->getWithItems($bomId);
        
        if (!$bom) {
            throw new ServiceException('BOM not found');
        }

        $requirements = [];
        
        foreach ($bom->items as $item) {
            $requiredQty = $item->required_quantity * $quantity;
            
            $requirements[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'required_quantity' => $requiredQty,
                'unit_id' => $item->unit_id,
                'unit_cost' => $item->unit_cost,
                'total_cost' => $requiredQty * $item->unit_cost,
                'scrap_percentage' => $item->scrap_percentage,
            ];
        }

        return [
            'bom_id' => $bomId,
            'bom_number' => $bom->bom_number,
            'product_id' => $bom->product_id,
            'product_name' => $bom->product->name,
            'quantity' => $quantity,
            'materials' => $requirements,
            'total_cost' => array_sum(array_column($requirements, 'total_cost')),
        ];
    }

    /**
     * Generate unique BOM number
     *
     * @return string
     */
    protected function generateBOMNumber(): string
    {
        $prefix = 'BOM';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Unset default BOMs for a product
     *
     * @param int $productId
     * @param int|null $exceptId
     * @return void
     */
    protected function unsetDefaultBOMs(int $productId, ?int $exceptId = null): void
    {
        $query = BillOfMaterial::where('product_id', $productId)
            ->where('is_default', true);
            
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        
        $query->update(['is_default' => false]);
    }

    /**
     * Create BOM items
     *
     * @param BillOfMaterial $bom
     * @param array $items
     * @return void
     */
    protected function createBOMItems(BillOfMaterial $bom, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $itemData['bom_id'] = $bom->id;
            $itemData['sequence'] = $itemData['sequence'] ?? $index + 1;
            $itemData['total_cost'] = ($itemData['quantity'] ?? 0) * ($itemData['unit_cost'] ?? 0);
            
            // Apply scrap percentage to total cost
            if (isset($itemData['scrap_percentage']) && $itemData['scrap_percentage'] > 0) {
                $scrapMultiplier = 1 + ($itemData['scrap_percentage'] / 100);
                $itemData['total_cost'] *= $scrapMultiplier;
            }
            
            BOMItem::create($itemData);
        }
    }

    /**
     * Update BOM items
     *
     * @param BillOfMaterial $bom
     * @param array $items
     * @return void
     */
    protected function updateBOMItems(BillOfMaterial $bom, array $items): void
    {
        // Delete existing items
        $bom->items()->delete();
        
        // Create new items
        $this->createBOMItems($bom, $items);
    }

    /**
     * Update estimated cost of BOM
     *
     * @param BillOfMaterial $bom
     * @return void
     */
    protected function updateEstimatedCost(BillOfMaterial $bom): void
    {
        $bom->refresh();
        $totalCost = $bom->calculateTotalCost();
        
        $bom->update(['estimated_cost' => $totalCost]);
    }
}
