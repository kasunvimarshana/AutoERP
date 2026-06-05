<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Customer\Application\Services\CustomerService;
use Modules\Customer\Presentation\Http\Requests\ListCustomerRequest;
use Modules\Customer\Presentation\Http\Requests\UpsertCustomerRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerListResource;
use Modules\Customer\Presentation\Http\Resources\CustomerResource;

final class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(ListCustomerRequest $request): AnonymousResourceCollection
    {
        return CustomerListResource::collection($this->customers->paginate($request->validated()));
    }

    public function show(int $customer): CustomerResource
    {
        return new CustomerResource($this->customers->find($customer));
    }

    public function store(UpsertCustomerRequest $request): JsonResponse
    {
        return (new CustomerResource($this->customers->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpsertCustomerRequest $request, int $customer): CustomerResource
    {
        return new CustomerResource($this->customers->update($customer, $request->validated()));
    }

    public function destroy(int $customer): JsonResponse
    {
        $this->customers->delete($customer);

        return response()->json(null, 204);
    }
}
