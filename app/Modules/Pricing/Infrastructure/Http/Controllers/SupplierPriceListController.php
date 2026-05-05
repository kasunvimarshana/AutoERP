<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
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
        $this->authorize('viewAny', SupplierPriceList::class);
        $validated = $request->validated();

        $assignments = $this->findSupplierPriceListService->paginateBySupplier(
            tenantId: (int) $request->input('tenant_id', $request->header('X-Tenant-ID')),
            supplierId: $supplier,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new SupplierPriceListCollection($assignments);
    }

    public function store(StoreSupplierPriceListRequest $request, int $supplier): JsonResponse
    {
        $this->authorize('create', SupplierPriceList::class);
        $payload = $request->validated();
        $payload['supplier_id'] = $supplier;

        $assignment = $this->createSupplierPriceListService->execute($payload);

        return (new SupplierPriceListResource($assignment))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function destroy(int $supplier, int $assignment): JsonResponse
    {
        $foundAssignment = $this->findSupplierPriceListService->find($assignment);

        if (! $foundAssignment || $foundAssignment->getSupplierId() !== $supplier) {
            throw new NotFoundHttpException('Supplier price list assignment not found.');
        }

        $this->authorize('delete', $foundAssignment);
        $this->deleteSupplierPriceListService->execute(['id' => $assignment]);

        return Response::json(['message' => 'Supplier price list assignment deleted successfully']);
    }
}
