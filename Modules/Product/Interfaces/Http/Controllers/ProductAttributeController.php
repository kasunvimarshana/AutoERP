<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Product\Application\Commands\DeleteProductAttributeCommand;
use Modules\Product\Application\Commands\SetProductAttributesCommand;
use Modules\Product\Application\Services\ProductAttributeService;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Interfaces\Http\Requests\SetProductAttributesRequest;
use Modules\Product\Interfaces\Http\Resources\ProductAttributeResource;

class ProductAttributeController extends BaseController
{
    public function __construct(
        private readonly ProductAttributeService $productAttributeService,
    ) {}

    /**
     * List all dynamic attributes for a product, ordered by sort_order.
     */
    public function index(int $productId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        if (! $this->productAttributeService->productExists($productId, $tenantId)) {
            return $this->error('Product not found', status: 404);
        }

        $attributes = $this->productAttributeService->listAttributes($productId, $tenantId);

        return $this->success(
            data: array_map(
                fn (ProductAttribute $attr) => (new ProductAttributeResource($attr))->resolve(),
                $attributes
            ),
            message: 'Product attributes retrieved successfully',
        );
    }

    /**
     * Replace all dynamic attributes for a product (idempotent set operation).
     */
    public function store(SetProductAttributesRequest $request, int $productId): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', '0');

        try {
            $attributes = $this->productAttributeService->setAttributes(new SetProductAttributesCommand(
                productId: $productId,
                tenantId: $tenantId,
                attributes: $request->validated('attributes'),
            ));

            return $this->success(
                data: array_map(
                    fn (ProductAttribute $attr) => (new ProductAttributeResource($attr))->resolve(),
                    $attributes
                ),
                message: 'Product attributes saved successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    /**
     * Remove a single product attribute by its ID.
     */
    public function destroy(int $productId, int $attributeId): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->productAttributeService->deleteAttribute(
                new DeleteProductAttributeCommand($attributeId, $productId, $tenantId)
            );

            return $this->success(message: 'Product attribute deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
