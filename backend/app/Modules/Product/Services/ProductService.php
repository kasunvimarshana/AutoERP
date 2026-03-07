<?php
namespace App\Modules\Product\Services;

use App\Helpers\PaginationHelper;
use App\Interfaces\MessageBrokerInterface;
use App\Modules\Product\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private MessageBrokerInterface $messageBroker,
    ) {}

    public function listProducts(array $filters, int $tenantId): mixed
    {
        $filters['tenant_id'] = $tenantId;
        ['per_page' => $perPage, 'page' => $page] = PaginationHelper::fromRequest(request());

        $query = $this->productRepository->all($filters);
        return PaginationHelper::paginate($query, $perPage, $page);
    }

    public function getProduct(int $id): mixed
    {
        return $this->productRepository->find($id, ['inventories']);
    }

    public function createProduct(array $data, int $tenantId): mixed
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id'] = $tenantId;
            $product = $this->productRepository->create($data);

            $this->messageBroker->publish('product.created', [
                'product_id' => $product->id,
                'tenant_id' => $tenantId,
                'name' => $product->name,
            ]);

            return $product;
        });
    }

    public function updateProduct(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepository->update($id, $data);

            $this->messageBroker->publish('product.updated', [
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
            ]);

            return $product;
        });
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepository->find($id);
        $result = $this->productRepository->delete($id);

        if ($result) {
            $this->messageBroker->publish('product.deleted', [
                'product_id' => $id,
                'tenant_id' => $product->tenant_id,
            ]);
        }

        return $result;
    }
}
