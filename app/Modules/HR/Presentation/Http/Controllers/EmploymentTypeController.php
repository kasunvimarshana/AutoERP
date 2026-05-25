<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\CreateEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\DeleteEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\GetEmploymentTypeServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\ListEmploymentTypesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\EmploymentTypes\UpdateEmploymentTypeServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListEmploymentTypeRequest;
use Modules\HR\Presentation\Http\Requests\UpsertEmploymentTypeRequest;
use Modules\HR\Presentation\Http\Resources\EmploymentTypeResource;

final class EmploymentTypeController extends Controller
{
    public function __construct(
        private readonly ListEmploymentTypesServiceInterface $listService,
        private readonly GetEmploymentTypeServiceInterface $getService,
        private readonly CreateEmploymentTypeServiceInterface $createService,
        private readonly UpdateEmploymentTypeServiceInterface $updateService,
        private readonly DeleteEmploymentTypeServiceInterface $deleteService,
    ) {
    }

    public function index(ListEmploymentTypeRequest $request): JsonResponse
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
            'data' => EmploymentTypeResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|EmploymentTypeResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new EmploymentTypeResource($result->valueOrFail());
    }

    public function store(UpsertEmploymentTypeRequest $request): JsonResponse|EmploymentTypeResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new EmploymentTypeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertEmploymentTypeRequest $request, int|string $id): JsonResponse|EmploymentTypeResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new EmploymentTypeResource($result->valueOrFail());
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