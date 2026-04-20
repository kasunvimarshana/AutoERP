<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Infrastructure\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierController extends AuthorizedController
{
    public function __construct(
        protected CreateSupplierServiceInterface $createSupplierService,
        protected UpdateSupplierServiceInterface $updateSupplierService,
        protected DeleteSupplierServiceInterface $deleteSupplierService,
        protected FindSupplierServiceInterface $findSupplierService,
    ) {}

    public function index(ListSupplierRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'org_unit_id' => $validated['org_unit_id'] ?? null,
            'supplier_code' => $validated['supplier_code'] ?? null,
            'name' => $validated['name'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
            'currency_id' => $validated['currency_id'] ?? null,
            'ap_account_id' => $validated['ap_account_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $suppliers = $this->findSupplierService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );

        return (new SupplierCollection($suppliers))->response();
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');

        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }

        $supplier = $this->createSupplierService->execute($payload);

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $supplier): SupplierResource
    {
        $foundSupplier = $this->findSupplierOrFail($supplier);
        $this->authorize('view', $foundSupplier);

        return new SupplierResource($foundSupplier);
    }

    public function update(UpdateSupplierRequest $request, int $supplier): SupplierResource
    {
        $foundSupplier = $this->findSupplierOrFail($supplier);
        $this->authorize('update', $foundSupplier);

        $payload = $request->validated();
        $avatarFile = $request->file('user.avatar');
        if ($avatarFile !== null) {
            $payload['user'] ??= [];
            $payload['user']['avatar'] = $avatarFile;
        }
        $payload['id'] = $supplier;

        return new SupplierResource($this->updateSupplierService->execute($payload));
    }

    public function destroy(int $supplier): JsonResponse
    {
        $foundSupplier = $this->findSupplierOrFail($supplier);
        $this->authorize('delete', $foundSupplier);

        $this->deleteSupplierService->execute(['id' => $supplier]);

        return Response::json(['message' => 'Supplier deleted successfully']);
    }

    private function findSupplierOrFail(int $supplierId): Supplier
    {
        $supplier = $this->findSupplierService->find($supplierId);

        if (! $supplier) {
            throw new NotFoundHttpException('Supplier not found.');
        }

        return $supplier;
    }
}
