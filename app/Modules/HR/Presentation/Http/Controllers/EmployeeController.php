<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\Employees\CreateEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\DeleteEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\GetEmployeeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\ListEmployeesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Employees\UpdateEmployeeServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmployeeRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmployeeRequest;
use Modules\HR\Presentation\Http\Resources\EmployeeResource;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly ListEmployeesServiceInterface $listService,
        private readonly GetEmployeeServiceInterface $getService,
        private readonly CreateEmployeeServiceInterface $createService,
        private readonly UpdateEmployeeServiceInterface $updateService,
        private readonly DeleteEmployeeServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmployeeRequest $request): JsonResponse
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
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmployeeResource($result->valueOrFail());
    }

    public function store(UpsertEmployeeRequest $request): JsonResponse|EmployeeResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmployeeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmployeeRequest $request, int|string $id): JsonResponse|EmployeeResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmployeeResource($result->valueOrFail());
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