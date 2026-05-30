<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Customer\Application\Contracts\Services\CustomerManagementServiceInterface;
use Modules\Customer\Presentation\Http\Requests\ListCustomerRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerDeactivateUserAccessRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerFinanceDefaultsRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerLinkUserRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerLookupRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerStatusTransitionRequest;
use Modules\Customer\Presentation\Http\Requests\CustomerUserAccessRequest;
use Modules\Customer\Presentation\Http\Requests\UpsertCustomerRequest;
use Modules\Customer\Presentation\Http\Resources\CustomerResource;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerManagementServiceInterface $service,
    ) {
    }

    public function index(ListCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->service->listCustomers($validated, $perPage, $page);

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
        $result = $this->service->getCustomer($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CustomerResource($result->valueOrFail());
    }

    public function store(UpsertCustomerRequest $request): JsonResponse|CustomerResource
    {
        $result = $this->service->createCustomer($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CustomerResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCustomerRequest $request, int|string $id): JsonResponse|CustomerResource
    {
        $result = $this->service->updateCustomer($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->service->safeDeleteCustomer($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(null, 204);
    }

    public function status(CustomerStatusTransitionRequest $request, int|string $id): JsonResponse|CustomerResource
    {
        $validated = $request->validated();
        $result = $this->service->changeStatus($id, (string) $validated['status'], $validated['reason'] ?? null);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CustomerResource($result->valueOrFail());
    }

    public function lookup(CustomerLookupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->lookupCustomers((string) ($validated['q'] ?? ''), (int) ($validated['limit'] ?? 20));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function validateForContext(int|string $id, string $context): JsonResponse
    {
        $result = $this->service->validateCustomerForContext($id, $context);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function creditCheck(CustomerFinanceDefaultsRequest $request, int|string $id): JsonResponse
    {
        $validated = $request->validated();
        $requestedAmount = isset($validated['requested_amount']) ? (float) $validated['requested_amount'] : null;

        $result = $this->service->checkCustomerCreditLimit($id, $requestedAmount);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function financeDefaults(int|string $id): JsonResponse
    {
        $result = $this->service->getFinanceDefaults($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function updateFinanceDefaults(CustomerFinanceDefaultsRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->updateFinanceDefaults($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function taxProfile(int|string $id): JsonResponse
    {
        $result = $this->service->getCustomerTaxProfile($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function listUserAccesses(int|string $id): JsonResponse
    {
        $result = $this->service->listCustomerUserAccounts($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function createUserAccess(CustomerUserAccessRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->createCustomerUserAccess($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function linkExistingUser(CustomerLinkUserRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->linkExistingUser($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function deactivateUserAccess(
        CustomerDeactivateUserAccessRequest $request,
        int|string $customerId,
        int|string $accessId,
    ): JsonResponse {
        $result = $this->service->deactivateCustomerUserAccess($customerId, $accessId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function unlinkUserAccess(int|string $customerId, int|string $accessId): JsonResponse
    {
        $result = $this->service->unlinkCustomerUserAccess($customerId, $accessId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CUSTOMER_NOT_FOUND' ? 404 : 422;

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
