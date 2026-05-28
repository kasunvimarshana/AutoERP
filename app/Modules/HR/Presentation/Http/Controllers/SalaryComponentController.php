<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\CreateSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\DeleteSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\GetSalaryComponentServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\ListSalaryComponentsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryComponents\UpdateSalaryComponentServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListSalaryComponentRequest;
use Modules\HR\Presentation\Http\Requests\UpsertSalaryComponentRequest;
use Modules\HR\Presentation\Http\Resources\SalaryComponentResource;

final class SalaryComponentController extends Controller
{
    public function __construct(
        private readonly ListSalaryComponentsServiceInterface $listService,
        private readonly GetSalaryComponentServiceInterface $getService,
        private readonly CreateSalaryComponentServiceInterface $createService,
        private readonly UpdateSalaryComponentServiceInterface $updateService,
        private readonly DeleteSalaryComponentServiceInterface $deleteService,
    ) {
    }

    public function index(ListSalaryComponentRequest $request): JsonResponse
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
            'data' => SalaryComponentResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SalaryComponentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SalaryComponentResource($result->valueOrFail());
    }

    public function store(UpsertSalaryComponentRequest $request): JsonResponse|SalaryComponentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SalaryComponentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSalaryComponentRequest $request, int|string $id): JsonResponse|SalaryComponentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SalaryComponentResource($result->valueOrFail());
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
