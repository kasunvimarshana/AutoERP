<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Customer\Application\Contracts\UseCases\Customers\CreateCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\DeleteCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\GetCustomerServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\ListCustomersServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\Customers\UpdateCustomerServiceInterface;
use Modules\Customer\Presentation\Http\Requests\ListCustomerRequest;
use Modules\Customer\Presentation\Http\Requests\UpsertCustomerRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerResource;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly ListCustomersServiceInterface $listService,
        private readonly GetCustomerServiceInterface $getService,
        private readonly CreateCustomerServiceInterface $createService,
        private readonly UpdateCustomerServiceInterface $updateService,
        private readonly DeleteCustomerServiceInterface $deleteService,
    ) {
    }

    public function index(ListCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => CustomerResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CustomerResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CustomerResource($result->valueOrFail());
    }

    public function store(UpsertCustomerRequest $request): JsonResponse|CustomerResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CustomerResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCustomerRequest $request, int|string $id): JsonResponse|CustomerResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}