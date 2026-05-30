<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Supplier\Application\Contracts\Services\SupplierManagementServiceInterface;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierDeactivateUserAccessRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierFinanceDefaultsRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierLinkUserRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierLookupRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierStatusTransitionRequest;
use Modules\Supplier\Presentation\Http\Requests\SupplierUserAccessRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierResource;

final class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierManagementServiceInterface $service,
    ) {
    }

    public function index(ListSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->service->listSuppliers($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => SupplierResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SupplierResource
    {
        $result = $this->service->getSupplier($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SupplierResource($result->valueOrFail());
    }

    public function store(UpsertSupplierRequest $request): JsonResponse|SupplierResource
    {
        $result = $this->service->createSupplier($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SupplierResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSupplierRequest $request, int|string $id): JsonResponse|SupplierResource
    {
        $result = $this->service->updateSupplier($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->service->safeDeleteSupplier($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(null, 204);
    }

    public function status(SupplierStatusTransitionRequest $request, int|string $id): JsonResponse|SupplierResource
    {
        $validated = $request->validated();
        $result = $this->service->changeStatus($id, (string) $validated['status'], $validated['reason'] ?? null);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SupplierResource($result->valueOrFail());
    }

    public function lookup(SupplierLookupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->lookupSuppliers((string) ($validated['q'] ?? ''), (int) ($validated['limit'] ?? 20));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function validateForContext(int|string $id, string $context): JsonResponse
    {
        $result = $this->service->validateSupplierForContext($id, $context);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function financeDefaults(int|string $id): JsonResponse
    {
        $result = $this->service->getFinanceDefaults($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function updateFinanceDefaults(SupplierFinanceDefaultsRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->updateFinanceDefaults($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function listUserAccesses(int|string $id): JsonResponse
    {
        $result = $this->service->listSupplierUserAccounts($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function createUserAccess(SupplierUserAccessRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->createSupplierUserAccess($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function linkExistingUser(SupplierLinkUserRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->linkExistingUser($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function deactivateUserAccess(
        SupplierDeactivateUserAccessRequest $request,
        int|string $supplierId,
        int|string $accessId,
    ): JsonResponse {
        $result = $this->service->deactivateSupplierUserAccess($supplierId, $accessId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function unlinkUserAccess(int|string $supplierId, int|string $accessId): JsonResponse
    {
        $result = $this->service->unlinkSupplierUserAccess($supplierId, $accessId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SUPPLIER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(null, 204);
    }

    private function normalizeResponseValue(mixed $value): mixed
    {
        if ($value instanceof DataRecord) {
            return $value->toArray();
        }

        return $value;
    }
}
