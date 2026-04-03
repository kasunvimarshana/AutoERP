<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\UoM\Application\Contracts\CreateProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\DeleteProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\FindProductUomSettingServiceInterface;
use Modules\UoM\Application\Contracts\UpdateProductUomSettingServiceInterface;
use Modules\UoM\Application\DTOs\ProductUomSettingData;
use Modules\UoM\Application\DTOs\UpdateProductUomSettingData;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Infrastructure\Http\Requests\StoreProductUomSettingRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateProductUomSettingRequest;
use Modules\UoM\Infrastructure\Http\Resources\ProductUomSettingCollection;
use Modules\UoM\Infrastructure\Http\Resources\ProductUomSettingResource;

class ProductUomSettingController extends AuthorizedController
{
    public function __construct(
        protected FindProductUomSettingServiceInterface $findService,
        protected CreateProductUomSettingServiceInterface $createService,
        protected UpdateProductUomSettingServiceInterface $updateService,
        protected DeleteProductUomSettingServiceInterface $deleteService,
    ) {}

    public function index(Request $request): ProductUomSettingCollection
    {
        $this->authorize('viewAny', ProductUomSetting::class);
        $filters = $request->only(['tenant_id', 'product_id', 'is_active']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $settings = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new ProductUomSettingCollection($settings);
    }

    public function store(StoreProductUomSettingRequest $request): JsonResponse
    {
        $this->authorize('create', ProductUomSetting::class);
        $validated = $request->validated();

        $dto = ProductUomSettingData::fromArray([
            'tenantId'       => $validated['tenant_id'],
            'productId'      => $validated['product_id'],
            'baseUomId'      => $validated['base_uom_id'] ?? null,
            'purchaseUomId'  => $validated['purchase_uom_id'] ?? null,
            'salesUomId'     => $validated['sales_uom_id'] ?? null,
            'inventoryUomId' => $validated['inventory_uom_id'] ?? null,
            'purchaseFactor' => $validated['purchase_factor'] ?? 1.0,
            'salesFactor'    => $validated['sales_factor'] ?? 1.0,
            'inventoryFactor'=> $validated['inventory_factor'] ?? 1.0,
            'isActive'       => $validated['is_active'] ?? true,
        ]);

        $setting = $this->createService->execute($dto->toArray());

        return (new ProductUomSettingResource($setting))->response()->setStatusCode(201);
    }

    public function show(int $id): ProductUomSettingResource
    {
        $setting = $this->findService->find($id);
        if (! $setting) {
            abort(404);
        }
        $this->authorize('view', $setting);

        return new ProductUomSettingResource($setting);
    }

    public function update(UpdateProductUomSettingRequest $request, int $id): ProductUomSettingResource
    {
        $setting = $this->findService->find($id);
        if (! $setting) {
            abort(404);
        }
        $this->authorize('update', $setting);

        $validated = $request->validated();
        $payload   = ['id' => $id];

        if (array_key_exists('base_uom_id', $validated)) {
            $payload['baseUomId'] = $validated['base_uom_id'];
        }
        if (array_key_exists('purchase_uom_id', $validated)) {
            $payload['purchaseUomId'] = $validated['purchase_uom_id'];
        }
        if (array_key_exists('sales_uom_id', $validated)) {
            $payload['salesUomId'] = $validated['sales_uom_id'];
        }
        if (array_key_exists('inventory_uom_id', $validated)) {
            $payload['inventoryUomId'] = $validated['inventory_uom_id'];
        }
        if (array_key_exists('purchase_factor', $validated)) {
            $payload['purchaseFactor'] = $validated['purchase_factor'];
        }
        if (array_key_exists('sales_factor', $validated)) {
            $payload['salesFactor'] = $validated['sales_factor'];
        }
        if (array_key_exists('inventory_factor', $validated)) {
            $payload['inventoryFactor'] = $validated['inventory_factor'];
        }
        if (array_key_exists('is_active', $validated)) {
            $payload['isActive'] = $validated['is_active'];
        }

        $dto     = UpdateProductUomSettingData::fromArray($payload);
        $updated = $this->updateService->execute($dto->toArray() + ['id' => $id]);

        return new ProductUomSettingResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $setting = $this->findService->find($id);
        if (! $setting) {
            abort(404);
        }
        $this->authorize('delete', $setting);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Product UoM setting deleted successfully']);
    }
}
