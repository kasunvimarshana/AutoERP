<?php

namespace App\Modules\Manufacturing\Services;

use App\Modules\Manufacturing\DTOs\CreateBillOfMaterialDTO;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Repositories\BillOfMaterialRepository;
use App\Services\BaseService;

/**
 * Bill of Material Service
 *
 * Handles business logic for BOM management including CRUD and version control.
 */
class BillOfMaterialService extends BaseService
{
    public function __construct(
        protected BillOfMaterialRepository $bomRepository
    ) {}

    /**
     * Get all BOMs with pagination
     */
    public function getAllBOMs(int $perPage = 15)
    {
        return $this->bomRepository->paginate($perPage);
    }

    /**
     * Create a new bill of material
     */
    public function createBOM(CreateBillOfMaterialDTO $dto): BillOfMaterial
    {
        return $this->transaction(function () use ($dto) {
            $this->logInfo('Creating new BOM', ['bom_number' => $dto->bomNumber]);

            // Create BOM
            $bom = $this->bomRepository->create([
                'product_id' => $dto->productId,
                'bom_number' => $dto->bomNumber,
                'version' => $dto->version,
                'is_active' => $dto->isActive,
                'effective_date' => $dto->effectiveDate,
                'notes' => $dto->notes,
            ]);

            // Create BOM items
            if ($dto->items) {
                foreach ($dto->items as $item) {
                    $bom->items()->create([
                        'component_product_id' => $item['component_product_id'],
                        'quantity' => $item['quantity'],
                        'uom_id' => $item['uom_id'] ?? null,
                        'scrap_factor' => $item['scrap_factor'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Load relationships
            $bom->load(['product', 'items.componentProduct', 'items.uom']);

            $this->logInfo('BOM created successfully', ['id' => $bom->id]);

            return $bom;
        });
    }

    /**
     * Update a bill of material
     */
    public function updateBOM(int $id, CreateBillOfMaterialDTO $dto): BillOfMaterial
    {
        return $this->transaction(function () use ($id, $dto) {
            $bom = $this->bomRepository->findOrFail($id);

            $this->logInfo('Updating BOM', ['id' => $id]);

            // Update BOM
            $this->bomRepository->update($id, [
                'product_id' => $dto->productId,
                'bom_number' => $dto->bomNumber,
                'version' => $dto->version,
                'is_active' => $dto->isActive,
                'effective_date' => $dto->effectiveDate,
                'notes' => $dto->notes,
            ]);

            // Update items if provided
            if ($dto->items) {
                // Delete existing items
                $bom->items()->delete();

                // Create new items
                foreach ($dto->items as $item) {
                    $bom->items()->create([
                        'component_product_id' => $item['component_product_id'],
                        'quantity' => $item['quantity'],
                        'uom_id' => $item['uom_id'] ?? null,
                        'scrap_factor' => $item['scrap_factor'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $bom->refresh();
            $bom->load(['product', 'items.componentProduct', 'items.uom']);

            $this->logInfo('BOM updated successfully', ['id' => $id]);

            return $bom;
        });
    }

    /**
     * Delete a bill of material
     */
    public function deleteBOM(int $id): bool
    {
        $this->logInfo('Deleting BOM', ['id' => $id]);

        $result = $this->bomRepository->delete($id);

        if ($result) {
            $this->logInfo('BOM deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Get a BOM by ID
     */
    public function getBOMById(int $id): BillOfMaterial
    {
        return $this->bomRepository->findWithItems($id);
    }

    /**
     * Get latest active BOM for a product
     */
    public function getLatestActiveBOMForProduct(int $productId): ?BillOfMaterial
    {
        return $this->bomRepository->getLatestActiveForProduct($productId);
    }

    /**
     * Get all BOMs for a product
     */
    public function getBOMsForProduct(int $productId)
    {
        return $this->bomRepository->getAllForProduct($productId);
    }

    /**
     * Create a new version of an existing BOM
     */
    public function createNewVersion(int $bomId): BillOfMaterial
    {
        return $this->transaction(function () use ($bomId) {
            $originalBom = $this->bomRepository->findWithItems($bomId);

            $this->logInfo('Creating new BOM version', ['original_id' => $bomId]);

            // Deactivate old version
            $this->bomRepository->update($bomId, ['is_active' => false]);

            // Create new version
            $newVersion = $originalBom->version + 1;
            $newBom = $this->bomRepository->create([
                'product_id' => $originalBom->product_id,
                'bom_number' => $originalBom->bom_number,
                'version' => $newVersion,
                'is_active' => true,
                'effective_date' => now()->toDateString(),
                'notes' => "Version {$newVersion} - Copied from version {$originalBom->version}",
            ]);

            // Copy items
            foreach ($originalBom->items as $item) {
                $newBom->items()->create([
                    'component_product_id' => $item->component_product_id,
                    'quantity' => $item->quantity,
                    'uom_id' => $item->uom_id,
                    'scrap_factor' => $item->scrap_factor,
                    'notes' => $item->notes,
                ]);
            }

            $newBom->load(['product', 'items.componentProduct', 'items.uom']);

            $this->logInfo('New BOM version created', ['id' => $newBom->id, 'version' => $newVersion]);

            return $newBom;
        });
    }

    /**
     * Search BOMs
     */
    public function searchBOMs(string $search)
    {
        return $this->bomRepository->search($search);
    }
}
