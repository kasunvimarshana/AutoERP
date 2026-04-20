<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Supplier\Application\Contracts\CreateSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierProductServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierProductServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Infrastructure\Http\Requests\ListSupplierProductRequest;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierProductRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierProductRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierProductCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierProductResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierProductController extends AuthorizedController
{
    public function __construct(
        protected FindSupplierServiceInterface $findSupplierService,
        protected FindSupplierProductServiceInterface $findSupplierProductService,
        protected CreateSupplierProductServiceInterface $createSupplierProductService,
        protected UpdateSupplierProductServiceInterface $updateSupplierProductService,
        protected DeleteSupplierProductServiceInterface $deleteSupplierProductService,
    ) {}

    public function index(int $supplierId, ListSupplierProductRequest $request): SupplierProductCollection
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('view', $supplier);

        $validated = $request->validated();
        $supplierProducts = $this->findSupplierProductService->paginateBySupplier(
            tenantId: $supplier->getTenantId(),
            supplierId: $supplierId,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new SupplierProductCollection($supplierProducts);
    }

    public function store(StoreSupplierProductRequest $request, int $supplierId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $supplierProduct = $this->createSupplierProductService->execute($payload);

        return (new SupplierProductResource($supplierProduct))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateSupplierProductRequest $request, int $supplierId, int $supplierProductId): SupplierProductResource
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $supplierProduct = $this->findSupplierProductOrFail($supplierProductId, $supplierId);
        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $payload['id'] = $supplierProduct->getId();

        return new SupplierProductResource($this->updateSupplierProductService->execute($payload));
    }

    public function destroy(int $supplierId, int $supplierProductId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $supplierProduct = $this->findSupplierProductOrFail($supplierProductId, $supplierId);
        $this->deleteSupplierProductService->execute(['id' => $supplierProduct->getId()]);

        return Response::json(['message' => 'Supplier product deleted successfully']);
    }

    private function findSupplierOrFail(int $supplierId): Supplier
    {
        $supplier = $this->findSupplierService->find($supplierId);

        if (! $supplier) {
            throw new NotFoundHttpException('Supplier not found.');
        }

        return $supplier;
    }

    private function findSupplierProductOrFail(int $supplierProductId, int $supplierId): SupplierProduct
    {
        $supplierProduct = $this->findSupplierProductService->find($supplierProductId);
        if (! $supplierProduct || $supplierProduct->getSupplierId() !== $supplierId) {
            throw new NotFoundHttpException('Supplier product not found.');
        }

        return $supplierProduct;
    }
}
