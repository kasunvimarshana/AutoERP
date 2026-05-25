<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\CreateCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\DeleteCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\GetCustomerAddressServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\ListCustomerAddressesServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerAddresses\UpdateCustomerAddressServiceInterface;
use Modules\Customer\Presentation\Http\Requests\ListCustomerAddressRequest;
use Modules\Customer\Presentation\Http\Requests\UpsertCustomerAddressRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerAddressResource;

final class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly ListCustomerAddressesServiceInterface $listService,
        private readonly GetCustomerAddressServiceInterface $getService,
        private readonly CreateCustomerAddressServiceInterface $createService,
        private readonly UpdateCustomerAddressServiceInterface $updateService,
        private readonly DeleteCustomerAddressServiceInterface $deleteService,
    ) {
    }

    public function index(ListCustomerAddressRequest $request): JsonResponse
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
            'data' => CustomerAddressResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CustomerAddressResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CustomerAddressResource($result->valueOrFail());
    }

    public function store(UpsertCustomerAddressRequest $request): JsonResponse|CustomerAddressResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CustomerAddressResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCustomerAddressRequest $request, int|string $id): JsonResponse|CustomerAddressResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerAddressResource($result->valueOrFail());
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