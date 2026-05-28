<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\Services\HrEmployeeManagementServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListDesignationRequest;
use Modules\HR\Presentation\Http\Requests\UpsertDesignationRequest;
use Modules\HR\Presentation\Http\Resources\DesignationResource;

final class DesignationController extends Controller
{
    public function __construct(
        private readonly HrEmployeeManagementServiceInterface $service,
    ) {
    }

    public function index(ListDesignationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->service->listDesignations($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => DesignationResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|DesignationResource
    {
        $result = $this->service->getDesignation($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new DesignationResource($result->valueOrFail());
    }

    public function store(UpsertDesignationRequest $request): JsonResponse|DesignationResource
    {
        $result = $this->service->createDesignation($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new DesignationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertDesignationRequest $request, int|string $id): JsonResponse|DesignationResource
    {
        $result = $this->service->updateDesignation($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new DesignationResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        return response()->json(['message' => 'Hard delete is disabled for designations.'], 422);
    }
}
