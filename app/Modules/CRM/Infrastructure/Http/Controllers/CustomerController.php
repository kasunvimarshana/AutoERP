<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\CRM\Application\Contracts\CustomerServiceInterface;
use Modules\CRM\Application\DTOs\CustomerData;
use Modules\CRM\Infrastructure\Http\Resources\CustomerResource;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

class CustomerController extends BaseController
{
    public function __construct(CustomerServiceInterface $service)
    {
        parent::__construct($service, CustomerResource::class, CustomerData::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return CustomerModel::class;
    }

    /**
     * List customers with optional filters.
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'type']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
            $request->input('include'),
        );

        return CustomerResource::collection($paginator);
    }

    /**
     * Create a new customer.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\CRM\Application\Contracts\CustomerServiceInterface $service */
        $service = $this->service;
        $customer = $service->createCustomer($request->all());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single customer.
     */
    public function show(string $id): JsonResponse
    {
        $customer = $this->service->find($id);

        return (new CustomerResource($customer))->response();
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\CRM\Application\Contracts\CustomerServiceInterface $service */
        $service = $this->service;
        $customer = $service->updateCustomer($id, $request->all());

        return (new CustomerResource($customer))->response();
    }

    /**
     * Delete a customer (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
