<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\DeleteSupplierPriceListServiceInterface;
use Modules\Pricing\Application\Contracts\FindSupplierPriceListServiceInterface;
use Modules\Pricing\Infrastructure\Http\Requests\ListAssignmentRequest;
use Modules\Pricing\Infrastructure\Http\Requests\StoreSupplierPriceListRequest;
use Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListCollection;
use Modules\Pricing\Infrastructure\Http\Resources\SupplierPriceListResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierPriceListController extends AuthorizedController
{
    public function __construct(
        protected CreateSupplierPriceListServiceInterface $createSupplierPriceListService,
        protected FindSupplierPriceListServiceInterface $findSupplierPriceListService,
        protected DeleteSupplierPriceListServiceInterface $deleteSupplierPriceListService,
    ) {}

    public function index(int $supplier, ListAssignmentRequest $request): SupplierPriceListCollection
    {
        $validated = $request->validated();
        $tenantId = (int) ($validated['tenant_id'] ?? $this->resolveTenantId($request));

        $assignments = $this->findSupplierPriceListService->paginateBySupplier(
            tenantId: $tenantId,
            supplierId: $supplier,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new SupplierPriceListCollection($assignments);
    }

    public function store(StoreSupplierPriceListRequest $request, int $supplier): JsonResponse
    {
        $payload = $request->validated();
        $payload['supplier_id'] = $supplier;

        $assignment = $this->createSupplierPriceListService->execute($payload);

        return (new SupplierPriceListResource($assignment))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(Request $request, int $supplier, int $assignment): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $foundAssignment = $this->findSupplierPriceListService->find($assignment);

        if (
            ! $foundAssignment
            || $foundAssignment->getTenantId() !== $tenantId
            || $foundAssignment->getSupplierId() !== $supplier
        ) {
            throw new NotFoundHttpException('Supplier price list assignment not found.');
        }

        $this->deleteSupplierPriceListService->execute(['id' => $assignment]);

        return Response::json(['message' => 'Supplier price list assignment deleted successfully']);
    }

    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->user()?->tenant_id ?? $request->header('X-Tenant-ID', '0'));
    }
}
