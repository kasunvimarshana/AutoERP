<?php

namespace App\Modules\Product\Services;

use App\Core\Services\BaseService;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService extends BaseService
{
    /**
     * ProductService constructor.
     *
     * @param ProductRepositoryInterface $repository
     */
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new product with slug generation
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            
            // Ensure SKU uniqueness
            if (!empty($data['sku'])) {
                $existing = $this->repository->findBySku($data['sku']);
                if ($existing) {
                    throw new ServiceException('SKU already exists');
                }
            }
            
            // Calculate profit margin if needed
            if (isset($data['buying_price']) && isset($data['selling_price'])) {
                $data = $this->calculateProfitMargin($data);
            }
            
            $product = $this->repository->create($data);
            
            DB::commit();
            
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Update product with slug regeneration if name changed
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $product = $this->repository->findById($id);
            
            if (!$product) {
                throw new ServiceException('Product not found');
            }
            
            // Regenerate slug if name changed and slug not provided
            if (isset($data['name']) && $data['name'] !== $product->name && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            
            // Ensure SKU uniqueness
            if (!empty($data['sku']) && $data['sku'] !== $product->sku) {
                $existing = $this->repository->findBySku($data['sku']);
                if ($existing && $existing->id !== $id) {
                    throw new ServiceException('SKU already exists');
                }
            }
            
            // Recalculate profit margin if prices changed
            if ((isset($data['buying_price']) && $data['buying_price'] != $product->buying_price) ||
                (isset($data['selling_price']) && $data['selling_price'] != $product->selling_price)) {
                $data = $this->calculateProfitMargin($data, $product);
            }
            
            $this->repository->update($id, $data);
            
            DB::commit();
            
            return $this->repository->findById($id);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Calculate profit margin
     *
     * @param array $data
     * @param mixed|null $product
     * @return array
     */
    protected function calculateProfitMargin(array $data, $product = null): array
    {
        $buyingPrice = $data['buying_price'] ?? ($product ? $product->buying_price : 0);
        $sellingPrice = $data['selling_price'] ?? ($product ? $product->selling_price : 0);
        
        // Apply discounts if present
        if (isset($data['buying_discount_type']) && isset($data['buying_discount_value'])) {
            if ($data['buying_discount_type'] === 'flat') {
                $buyingPrice -= $data['buying_discount_value'];
            } elseif ($data['buying_discount_type'] === 'percentage') {
                $buyingPrice -= ($buyingPrice * $data['buying_discount_value'] / 100);
            }
        }
        
        if (isset($data['selling_discount_type']) && isset($data['selling_discount_value'])) {
            if ($data['selling_discount_type'] === 'flat') {
                $sellingPrice -= $data['selling_discount_value'];
            } elseif ($data['selling_discount_type'] === 'percentage') {
                $sellingPrice -= ($sellingPrice * $data['selling_discount_value'] / 100);
            }
        }
        
        // Calculate margin
        if ($buyingPrice > 0) {
            $marginPercentage = (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
            $data['calculated_margin'] = round($marginPercentage, 2);
        }
        
        return $data;
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByCategory(int $categoryId)
    {
        return $this->repository->getByCategory($categoryId);
    }

    /**
     * Search products
     *
     * @param string $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query, array $filters = [])
    {
        return $this->repository->search($query, $filters);
    }

    /**
     * Get low stock products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts()
    {
        return $this->repository->getLowStockProducts();
    }

    /**
     * Get out of stock products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockProducts()
    {
        return $this->repository->getOutOfStockProducts();
    }

    /**
     * Calculate final price with all discounts and taxes
     *
     * @param int $productId
     * @param float $quantity
     * @param array $context
     * @return array
     */
    public function calculateFinalPrice(int $productId, float $quantity = 1, array $context = []): array
    {
        $product = $this->repository->findById($productId);
        
        if (!$product) {
            throw new ServiceException('Product not found');
        }
        
        $basePrice = $product->selling_price;
        
        // Apply product discounts
        $discountedPrice = $product->getFinalSellingPrice();
        
        // Calculate tax if applicable
        $taxAmount = 0;
        if ($product->tax_rate_id && $product->taxRate) {
            if ($product->is_tax_inclusive) {
                $taxAmount = $product->taxRate->calculateBaseFromTotal($discountedPrice) - $discountedPrice;
            } else {
                $taxAmount = $product->taxRate->calculateTax($discountedPrice);
            }
        }
        
        $finalPrice = $discountedPrice + ($product->is_tax_inclusive ? 0 : $taxAmount);
        $totalAmount = $finalPrice * $quantity;
        
        return [
            'product_id' => $productId,
            'quantity' => $quantity,
            'base_price' => $basePrice,
            'discounted_price' => $discountedPrice,
            'tax_amount' => $taxAmount,
            'final_price' => $finalPrice,
            'total_amount' => $totalAmount,
            'discount_applied' => $basePrice - $discountedPrice,
        ];
    }
}
