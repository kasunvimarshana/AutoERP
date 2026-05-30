<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\Services\HrEmployeeManagementServiceInterface;
use Modules\HR\Presentation\Http\Requests\EmployeeLookupRequest;
use Modules\HR\Presentation\Http\Requests\EmployeeStatusTransitionRequest;
use Modules\HR\Presentation\Http\Requests\EmployeeUserAccessDeactivateRequest;
use Modules\HR\Presentation\Http\Requests\EmployeeUserAccessRequest;
use Modules\HR\Presentation\Http\Requests\LinkExistingEmployeeUserRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeEmploymentDetailRequest;
use Modules\HR\Presentation\Http\Requests\ListEmployeeRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeResource;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly HrEmployeeManagementServiceInterface $service,
    ) {
    }

    public function index(ListEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->service->listEmployees($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => EmployeeResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmployeeResource
    {
        $result = $this->service->getEmployee($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeRequest $request): JsonResponse|EmployeeResource
    {
        $result = $this->service->createEmployee($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeRequest $request, int|string $id): JsonResponse|EmployeeResource
    {
        $result = $this->service->updateEmployee($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->service->safeDeleteEmployee($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(null, 204);
    }

    public function status(EmployeeStatusTransitionRequest $request, int|string $id): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->changeEmployeeStatus(
            $id,
            (string) $validated['employment_status'],
            $validated['reason'] ?? null,
        );

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function lookup(EmployeeLookupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->lookupEmployees((string) ($validated['q'] ?? ''), (int) ($validated['limit'] ?? 20));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function activeList(EmployeeLookupRequest $request): JsonResponse
    {
        $result = $this->service->listActiveEmployees((int) ($request->validated()['limit'] ?? 50));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function validateForAssignmentContext(int|string $id, string $context): JsonResponse
    {
        $result = $this->service->validateEmployeeForAssignmentContext($id, $context);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function byDepartment(int|string $departmentId): JsonResponse
    {
        $result = $this->service->getEmployeesByDepartment($departmentId);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function byDesignation(int|string $designationId): JsonResponse
    {
        $result = $this->service->getEmployeesByDesignation($designationId);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function employmentDetails(int|string $id): JsonResponse
    {
        $result = $this->service->getEmploymentDetails($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function updateEmploymentDetails(
        UpsertEmployeeEmploymentDetailRequest $request,
        int|string $id,
    ): JsonResponse {
        $result = $this->service->updateEmploymentDetails($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function listUserAccesses(int|string $id): JsonResponse
    {
        $result = $this->service->listEmployeeUserAccounts($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function createUserAccess(EmployeeUserAccessRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->createEmployeeUserAccess($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function linkExistingUser(LinkExistingEmployeeUserRequest $request, int|string $id): JsonResponse
    {
        $result = $this->service->linkExistingUserToEmployee($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())], 201);
    }

    public function deactivateUserAccess(
        EmployeeUserAccessDeactivateRequest $request,
        int|string $employeeId,
        int|string $accessId,
    ): JsonResponse {
        $result = $this->service->deactivateEmployeeUserAccess($employeeId, $accessId, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(['data' => $this->normalizeResponseValue($result->valueOrFail())]);
    }

    public function unlinkUserAccess(int|string $employeeId, int|string $accessId): JsonResponse
    {
        $result = $this->service->unlinkEmployeeUserAccess($employeeId, $accessId);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

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
