<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\CreateSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\DeleteSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\GetSalaryStructureLineServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\ListSalaryStructureLinesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\UpdateSalaryStructureLineServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListSalaryStructureLineRequest;
use Modules\HR\Presentation\Http\Requests\UpsertSalaryStructureLineRequest;
use Modules\HR\Presentation\Http\Resources\SalaryStructureLineResource;

final class SalaryStructureLineController extends Controller
{
    public function __construct(
        private readonly ListSalaryStructureLinesServiceInterface $listService,
        private readonly GetSalaryStructureLineServiceInterface $getService,
        private readonly CreateSalaryStructureLineServiceInterface $createService,
        private readonly UpdateSalaryStructureLineServiceInterface $updateService,
        private readonly DeleteSalaryStructureLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListSalaryStructureLineRequest $request): JsonResponse
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
            'data' => SalaryStructureLineResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|SalaryStructureLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SalaryStructureLineResource($result->valueOrFail());
    }

    public function store(UpsertSalaryStructureLineRequest $request): JsonResponse|SalaryStructureLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new SalaryStructureLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSalaryStructureLineRequest $request, int|string $id): JsonResponse|SalaryStructureLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new SalaryStructureLineResource($result->valueOrFail());
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