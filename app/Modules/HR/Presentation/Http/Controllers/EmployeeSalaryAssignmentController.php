<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\CreateEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\DeleteEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\GetEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\ListEmployeeSalaryAssignmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmployeeSalaryAssignments\UpdateEmployeeSalaryAssignmentServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeSalaryAssignmentRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeSalaryAssignmentRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeSalaryAssignmentResource;

final class EmployeeSalaryAssignmentController extends Controller
{
    public function __construct(
        private readonly ListEmployeeSalaryAssignmentsServiceInterface $listService,
        private readonly GetEmployeeSalaryAssignmentServiceInterface $getService,
        private readonly CreateEmployeeSalaryAssignmentServiceInterface $createService,
        private readonly UpdateEmployeeSalaryAssignmentServiceInterface $updateService,
        private readonly DeleteEmployeeSalaryAssignmentServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmployeeSalaryAssignmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => EmployeeSalaryAssignmentResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmployeeSalaryAssignmentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeSalaryAssignmentResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeSalaryAssignmentRequest $request): JsonResponse|EmployeeSalaryAssignmentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeSalaryAssignmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeSalaryAssignmentRequest $request, int|string $id): JsonResponse|EmployeeSalaryAssignmentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeSalaryAssignmentResource($result->valueOrFail());
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
