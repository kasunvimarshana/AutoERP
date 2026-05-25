<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\CreateCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\DeleteCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\GetCustomerContactServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\ListCustomerContactsServiceInterface;
use Modules\Customer\Application\Contracts\UseCases\CustomerContacts\UpdateCustomerContactServiceInterface;
use Modules\Customer\Presentation\Http\Requests\ListCustomerContactRequest;
use Modules\Customer\Presentation\Http\Requests\UpsertCustomerContactRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerContactResource;

final class CustomerContactController extends Controller
{
    public function __construct(
        private readonly ListCustomerContactsServiceInterface $listService,
        private readonly GetCustomerContactServiceInterface $getService,
        private readonly CreateCustomerContactServiceInterface $createService,
        private readonly UpdateCustomerContactServiceInterface $updateService,
        private readonly DeleteCustomerContactServiceInterface $deleteService,
    ) {
    }

    public function index(ListCustomerContactRequest $request): JsonResponse
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
            'data' => CustomerContactResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CustomerContactResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CustomerContactResource($result->valueOrFail());
    }

    public function store(UpsertCustomerContactRequest $request): JsonResponse|CustomerContactResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CustomerContactResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCustomerContactRequest $request, int|string $id): JsonResponse|CustomerContactResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerContactResource($result->valueOrFail());
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