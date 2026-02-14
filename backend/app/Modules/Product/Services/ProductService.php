<?php

namespace App\Modules\Product\Services;

use App\Base\BaseService;
use App\Modules\Product\Repositories\ProductRepository;
use App\Modules\Product\Repositories\ProductVariantRepository;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\DTOs\ProductVariantDTO;
use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Events\ProductUpdated;
use App\Modules\Product\Events\ProductDeleted;
use App\Modules\Product\Events\VariantCreated;
use App\Modules\Product\Events\VariantUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

class ProductService extends BaseService
{
    protected ProductVariantRepository $variantRepository;

    public function __construct(
        ProductRepository $repository,
        ProductVariantRepository $variantRepository
    ) {
        parent::__construct($repository);
        $this->variantRepository = $variantRepository;
    }

    public function createProduct(ProductDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $product = $this->repository->create($data);
            
            Event::dispatch(new ProductCreated($product));
            
            return $product;
        });
    }

    public function updateProduct(string $id, ProductDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $product = $this->repository->find($id);
            
            if (!$product) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $product->refresh();
                Event::dispatch(new ProductUpdated($product));
            }
            
            return $result;
        });
    }

    public function deleteProduct(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $product = $this->repository->find($id);
            
            if (!$product) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new ProductDeleted($product));
            }
            
            return $result;
        });
    }

    public function addVariant(string $productId, ProductVariantDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($productId, $dto) {
            $product = $this->repository->find($productId);
            
            if (!$product) {
                throw new \Exception('Product not found');
            }
            
            $data = $dto->toArray();
            $data['product_id'] = $productId;
            $data['tenant_id'] = $product->tenant_id;
            
            $variant = $this->variantRepository->create($data);
            
            Event::dispatch(new VariantCreated($variant));
            
            return $variant;
        });
    }

    public function updateVariant(string $variantId, ProductVariantDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($variantId, $dto) {
            $variant = $this->variantRepository->find($variantId);
            
            if (!$variant) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->variantRepository->update($variantId, $data);
            
            if ($result) {
                $variant->refresh();
                Event::dispatch(new VariantUpdated($variant));
            }
            
            return $result;
        });
    }

    public function deleteVariant(string $variantId): bool
    {
        return $this->executeInTransaction(function () use ($variantId) {
            $variant = $this->variantRepository->find($variantId);
            
            if (!$variant) {
                return false;
            }
            
            return $this->variantRepository->delete($variantId);
        });
    }

    public function getProductVariants(string $productId): Collection
    {
        return $this->variantRepository->findByProduct($productId, ['product', 'inventory']);
    }
}
