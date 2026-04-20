<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\DeleteSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierContactServiceInterface;
use Modules\Supplier\Application\Contracts\FindSupplierServiceInterface;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Infrastructure\Http\Requests\ListSupplierContactRequest;
use Modules\Supplier\Infrastructure\Http\Requests\StoreSupplierContactRequest;
use Modules\Supplier\Infrastructure\Http\Requests\UpdateSupplierContactRequest;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierContactCollection;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierContactResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SupplierContactController extends AuthorizedController
{
    public function __construct(
        protected FindSupplierServiceInterface $findSupplierService,
        protected FindSupplierContactServiceInterface $findSupplierContactService,
        protected CreateSupplierContactServiceInterface $createSupplierContactService,
        protected UpdateSupplierContactServiceInterface $updateSupplierContactService,
        protected DeleteSupplierContactServiceInterface $deleteSupplierContactService,
    ) {}

    public function index(int $supplierId, ListSupplierContactRequest $request): SupplierContactCollection
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('view', $supplier);

        $validated = $request->validated();
        $contacts = $this->findSupplierContactService->paginateBySupplier(
            tenantId: $supplier->getTenantId(),
            supplierId: $supplierId,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return new SupplierContactCollection($contacts);
    }

    public function store(StoreSupplierContactRequest $request, int $supplierId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $contact = $this->createSupplierContactService->execute($payload);

        return (new SupplierContactResource($contact))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateSupplierContactRequest $request, int $supplierId, int $contactId): SupplierContactResource
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $contact = $this->findContactOrFail($contactId, $supplierId);
        $payload = $request->validated();
        $payload['supplier_id'] = $supplierId;
        $payload['id'] = $contact->getId();

        return new SupplierContactResource($this->updateSupplierContactService->execute($payload));
    }

    public function destroy(int $supplierId, int $contactId): JsonResponse
    {
        $supplier = $this->findSupplierOrFail($supplierId);
        $this->authorize('update', $supplier);

        $contact = $this->findContactOrFail($contactId, $supplierId);
        $this->deleteSupplierContactService->execute(['id' => $contact->getId()]);

        return Response::json(['message' => 'Supplier contact deleted successfully']);
    }

    private function findSupplierOrFail(int $supplierId): Supplier
    {
        $supplier = $this->findSupplierService->find($supplierId);

        if (! $supplier) {
            throw new NotFoundHttpException('Supplier not found.');
        }

        return $supplier;
    }

    private function findContactOrFail(int $contactId, int $supplierId): SupplierContact
    {
        $contact = $this->findSupplierContactService->find($contactId);
        if (! $contact || $contact->getSupplierId() !== $supplierId) {
            throw new NotFoundHttpException('Supplier contact not found.');
        }

        return $contact;
    }
}
