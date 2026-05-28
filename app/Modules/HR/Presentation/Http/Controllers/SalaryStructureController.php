<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\CreateSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\DeleteSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\GetSalaryStructureServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\ListSalaryStructuresServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\UpdateSalaryStructureServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListSalaryStructureRequest;
use Modules\HR\Presentation\Http\Requests\UpsertSalaryStructureRequest;
use Modules\HR\Presentation\Http\Resources\SalaryStructureResource;

final class SalaryStructureController extends Controller
{
    public function __construct(
        private readonly ListSalaryStructuresServiceInterface $listService,
        private readonly GetSalaryStructureServiceInterface $getService,
        private readonly CreateSalaryStructureServiceInterface $createService,
        private readonly UpdateSalaryStructureServiceInterface $updateService,
        private readonly DeleteSalaryStructureServiceInterface $deleteService,
    ) {
    }

    public function index(ListSalaryStructureRequest $request): JsonResponse
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
            'data' => SalaryStructureResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SalaryStructureResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SalaryStructureResource($result->valueOrFail());
    }

    public function store(UpsertSalaryStructureRequest $request): JsonResponse|SalaryStructureResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SalaryStructureResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSalaryStructureRequest $request, int|string $id): JsonResponse|SalaryStructureResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SalaryStructureResource($result->valueOrFail());
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
