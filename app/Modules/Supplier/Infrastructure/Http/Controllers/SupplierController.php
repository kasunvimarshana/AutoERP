<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Supplier\Application\Contracts\SupplierServiceInterface;
use Modules\Supplier\Infrastructure\Http\Resources\SupplierResource;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierServiceInterface $supplierService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(SupplierResource::collection($this->supplierService->getAllSuppliers($tenantId)));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $supplier = $this->supplierService->createSupplier($tenantId, $request->all());
        return response()->json(new SupplierResource($supplier), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new SupplierResource($this->supplierService->getSupplier($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new SupplierResource($this->supplierService->updateSupplier($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->supplierService->deleteSupplier($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }
}
