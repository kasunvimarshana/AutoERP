<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Supplier\Application\Contracts\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierAddressServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Infrastructure\Http\Requests\ListSupplierAddressRequest;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierAddressRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierAddressRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierAddressResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierAddressController extends AuthorizedController
{
    public function __construct(
        protected FindSupplierServiceInterface $findSupplierService,
        protected FindSupplierAddressServiceInterface $findSupplierAddressService,
        protected CreateSupplierAddressServiceInterface $createSupplierAddressService,
        protected UpdateSupplierAddressServiceInterface $updateSupplierAddressService,
        protected DeleteSupplierAddressServiceInterface $deleteSupplierAddressService,
    ) {}

    public function index(int $supplierId, ListSupplierAddressRequest $request): SupplierAddressCollection
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('view', $supplier);

        $validated = $request->validated();
        $addresses = $this->findSupplierAddressService->paginateBySupplier(
            tenantId: $supplier->getTenantId(),
            supplierId: $supplierId,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new SupplierAddressCollection($addresses);
    }

    public function store(StoreSupplierAddressRequest $request, int $supplierId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $address = $this->createSupplierAddressService->execute($payload);

        return (new SupplierAddressResource($address))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateSupplierAddressRequest $request, int $supplierId, int $addressId): SupplierAddressResource
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $address = $this->findAddressOrFail($addressId, $supplierId);
        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $payload['id'] = $address->getId();

        return new SupplierAddressResource($this->updateSupplierAddressService->execute($payload));
    }

    public function destroy(int $supplierId, int $addressId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $address = $this->findAddressOrFail($addressId, $supplierId);
        $this->deleteSupplierAddressService->execute(['id' => $address->getId()]);

        return Response::json(['message' => 'Supplier address deleted successfully']);
    }

    private function findSupplierOrFail(int $supplierId): Supplier
    {
        $supplier = $this->findSupplierService->find($supplierId);

        if (! $supplier) {
            throw new NotFoundHttpException('Supplier not found.');
        }

        return $supplier;
    }

    private function findAddressOrFail(int $addressId, int $supplierId): SupplierAddress
    {
        $address = $this->findSupplierAddressService->find($addressId);
        if (! $address || $address->getSupplierId() !== $supplierId) {
            throw new NotFoundHttpException('Supplier address not found.');
        }

        return $address;
    }
}
