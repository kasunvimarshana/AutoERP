<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\Departments\CreateDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\GetDepartmentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\ListDepartmentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\Departments\UpdateDepartmentServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListDepartmentRequest;
use Modules\HR\Presentation\Http\Requests\UpsertDepartmentRequest;
use Modules\HR\Presentation\Http\Resources\DepartmentResource;

final class DepartmentController extends Controller
{
    public function __construct(
        private readonly ListDepartmentsServiceInterface $listService,
        private readonly GetDepartmentServiceInterface $getService,
        private readonly CreateDepartmentServiceInterface $createService,
        private readonly UpdateDepartmentServiceInterface $updateService,
        private readonly DeleteDepartmentServiceInterface $deleteService,
    ) {
    }

    public function index(ListDepartmentRequest $request): JsonResponse
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
            'data' => DepartmentResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|DepartmentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new DepartmentResource($result->valueOrFail());
    }

    public function store(UpsertDepartmentRequest $request): JsonResponse|DepartmentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new DepartmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertDepartmentRequest $request, int|string $id): JsonResponse|DepartmentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new DepartmentResource($result->valueOrFail());
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