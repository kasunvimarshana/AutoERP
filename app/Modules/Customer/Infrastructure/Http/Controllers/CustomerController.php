<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerServiceInterface $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(CustomerResource::collection($this->customerService->getAllCustomers($tenantId)));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $customer = $this->customerService->createCustomer($tenantId, $request->all());
        return response()->json(new CustomerResource($customer), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new CustomerResource($this->customerService->getCustomer($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new CustomerResource($this->customerService->updateCustomer($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->customerService->deleteCustomer($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }
}
