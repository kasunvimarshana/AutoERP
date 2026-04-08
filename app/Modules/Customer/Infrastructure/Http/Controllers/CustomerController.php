<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Customer\Application\Contracts\CustomerServiceInterface;
use Modules\Customer\Application\DTOs\CustomerData;
use Modules\Customer\Infrastructure\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Infrastructure\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Infrastructure\Http\Resources\CustomerResource;

/**
 * @OA\Tag(name="Customers", description="Customer management")
 */
final class CustomerController extends AuthorizedController
{
    public function __construct(private readonly CustomerServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/customer/customers",
     *     tags={"Customers"},
     *     summary="List customers",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive","blocked"})),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"individual","business"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list of customers")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['status', 'type']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return CustomerResource::collection(
            $this->service->list($filters, $perPage)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/customer/customers",
     *     tags={"Customers"},
     *     summary="Create a customer",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreCustomerRequest")),
     *     @OA\Response(response=201, description="Customer created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = CustomerData::fromArray($request->validated());
        $customer = $this->service->create($dto, $tenantId);

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/customer/customers/{id}",
     *     tags={"Customers"},
     *     summary="Get a customer by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Customer details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        return (new CustomerResource($this->service->find($id)))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/customer/customers/{id}",
     *     tags={"Customers"},
     *     summary="Update a customer",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateCustomerRequest")),
     *     @OA\Response(response=200, description="Customer updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $dto      = CustomerData::fromArray($request->validated());
        $customer = $this->service->update($id, $dto);

        return (new CustomerResource($customer))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/customer/customers/{id}",
     *     tags={"Customers"},
     *     summary="Delete a customer",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
