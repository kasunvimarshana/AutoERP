<?php

declare(strict_types=1);

namespace Modules\Procurement\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Procurement\Application\Commands\CreateSupplierCommand;
use Modules\Procurement\Application\Commands\DeleteSupplierCommand;
use Modules\Procurement\Application\Commands\UpdateSupplierCommand;
use Modules\Procurement\Application\Services\SupplierService;
use Modules\Procurement\Interfaces\Http\Requests\CreateSupplierRequest;
use Modules\Procurement\Interfaces\Http\Requests\UpdateSupplierRequest;
use Modules\Procurement\Interfaces\Http\Resources\SupplierResource;

class SupplierController extends BaseController
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->supplierService->listSuppliers($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($supplier) => (new SupplierResource($supplier))->resolve(),
                $result['items']
            ),
            message: 'Suppliers retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->createSupplier(new CreateSupplierCommand(
            tenantId: (int) $request->validated('tenant_id'),
            name: $request->validated('name'),
            contactName: $request->validated('contact_name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            address: $request->validated('address'),
            notes: $request->validated('notes'),
        ));

        return $this->success(
            data: (new SupplierResource($supplier))->resolve(),
            message: 'Supplier created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $supplier = $this->supplierService->findSupplierById($id, $tenantId);

        if ($supplier === null) {
            return $this->error('Supplier not found', status: 404);
        }

        return $this->success(
            data: (new SupplierResource($supplier))->resolve(),
            message: 'Supplier retrieved successfully',
        );
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $supplier = $this->supplierService->updateSupplier(new UpdateSupplierCommand(
                id: $id,
                tenantId: $tenantId,
                name: $request->validated('name'),
                contactName: $request->validated('contact_name'),
                email: $request->validated('email'),
                phone: $request->validated('phone'),
                address: $request->validated('address'),
                status: $request->validated('status'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new SupplierResource($supplier))->resolve(),
                message: 'Supplier updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->supplierService->deleteSupplier(new DeleteSupplierCommand($id, $tenantId));

            return $this->success(message: 'Supplier deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
