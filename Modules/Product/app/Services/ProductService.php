<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Product\Models\Product;
use Modules\Product\Repositories\ProductRepository;

/**
 * Product Service
 *
 * Contains business logic for Product operations
 * Extends BaseService for common service layer functionality
 */
class ProductService extends BaseService
{
    /**
     * ProductService constructor
     */
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new product
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate SKU uniqueness if provided
        if (isset($data['sku']) && $this->repository->skuExists($data['sku'])) {
            throw ValidationException::withMessages([
                'sku' => ['The SKU has already been taken.'],
            ]);
        }

        // Validate barcode uniqueness if provided
        if (isset($data['barcode']) && ! empty($data['barcode']) && $this->repository->barcodeExists($data['barcode'])) {
            throw ValidationException::withMessages([
                'barcode' => ['The barcode has already been taken.'],
            ]);
        }

        // Generate unique SKU if not provided
        if (! isset($data['sku'])) {
            $data['sku'] = $this->generateUniqueSKU();
        }

        // Set default values
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        if (! isset($data['type'])) {
            $data['type'] = 'goods';
        }

        return parent::create($data);
    }

    /**
     * Update product
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate SKU uniqueness if provided
        if (isset($data['sku']) && $this->repository->skuExists($data['sku'], $id)) {
            throw ValidationException::withMessages([
                'sku' => ['The SKU has already been taken.'],
            ]);
        }

        // Validate barcode uniqueness if provided
        if (isset($data['barcode']) && ! empty($data['barcode']) && $this->repository->barcodeExists($data['barcode'], $id)) {
            throw ValidationException::withMessages([
                'barcode' => ['The barcode has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get product with variants
     */
    public function getWithVariants(int $id): mixed
    {
        return $this->repository->findWithVariants($id);
    }

    /**
     * Get product with all relationships
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Search products
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get products by type
     */
    public function getByType(string $type): mixed
    {
        return $this->repository->getByType($type);
    }

    /**
     * Get active products
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get low stock products
     */
    public function getLowStock(): mixed
    {
        return $this->repository->getLowStock();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStock(): mixed
    {
        return $this->repository->getOutOfStock();
    }

    /**
     * Get featured products
     */
    public function getFeatured(): mixed
    {
        return $this->repository->getFeatured();
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): mixed
    {
        return $this->repository->getByCategory($categoryId);
    }

    /**
     * Update stock level
     */
    public function updateStock(int $id, int $quantity): bool
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Stock quantity cannot be negative.'],
            ]);
        }

        $product = $this->repository->findOrFail($id);

        if (! $product->track_inventory) {
            throw new ServiceException('This product does not track inventory.');
        }

        return $this->repository->updateStock($id, $quantity);
    }

    /**
     * Add stock (stock in)
     */
    public function addStock(int $id, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be greater than zero.'],
            ]);
        }

        $product = $this->repository->findOrFail($id);

        if (! $product->track_inventory) {
            throw new ServiceException('This product does not track inventory.');
        }

        return $this->repository->incrementStock($id, $quantity);
    }

    /**
     * Remove stock (stock out)
     */
    public function removeStock(int $id, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be greater than zero.'],
            ]);
        }

        $product = $this->repository->findOrFail($id);

        if (! $product->track_inventory) {
            throw new ServiceException('This product does not track inventory.');
        }

        if ($product->current_stock < $quantity) {
            throw new ServiceException('Insufficient stock. Current stock: '.$product->current_stock);
        }

        return $this->repository->decrementStock($id, $quantity);
    }

    /**
     * Change product status
     */
    public function changeStatus(int $id, string $status): mixed
    {
        if (! in_array($status, ['active', 'inactive', 'discontinued', 'out_of_stock'])) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status value.'],
            ]);
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Generate unique SKU
     */
    protected function generateUniqueSKU(string $prefix = 'PRD'): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $sku = Product::generateSKU($prefix);
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new ServiceException('Failed to generate unique SKU after maximum attempts');
            }
        } while ($this->repository->skuExists($sku));

        return $sku;
    }

    /**
     * Get product inventory statistics
     */
    public function getInventoryStatistics(int $productId): array
    {
        $product = $this->repository->findOrFail($productId);

        if (! $product->track_inventory) {
            return [
                'track_inventory' => false,
                'message' => 'This product does not track inventory',
            ];
        }

        return [
            'track_inventory' => true,
            'current_stock' => $product->current_stock,
            'reorder_level' => $product->reorder_level,
            'reorder_quantity' => $product->reorder_quantity,
            'min_stock_level' => $product->min_stock_level,
            'max_stock_level' => $product->max_stock_level,
            'needs_reorder' => $product->needsReorder(),
            'stock_status' => $product->stock_status,
            'stock_value' => $product->current_stock * $product->cost_price,
        ];
    }

    /**
     * Calculate product profitability
     */
    public function calculateProfitability(int $productId): array
    {
        $product = $this->repository->findOrFail($productId);

        return [
            'cost_price' => $product->cost_price,
            'selling_price' => $product->selling_price,
            'profit' => $product->profit,
            'profit_margin' => $product->profit_margin,
        ];
    }
}
