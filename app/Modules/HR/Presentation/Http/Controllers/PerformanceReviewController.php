<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\DeletePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\GetPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\ListPerformanceReviewsServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceReviews\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListPerformanceReviewRequest;
use Modules\HR\Presentation\Http\Requests\UpsertPerformanceReviewRequest;
use Modules\HR\Presentation\Http\Resources\PerformanceReviewResource;

final class PerformanceReviewController extends Controller
{
    public function __construct(
        private readonly ListPerformanceReviewsServiceInterface $listService,
        private readonly GetPerformanceReviewServiceInterface $getService,
        private readonly CreatePerformanceReviewServiceInterface $createService,
        private readonly UpdatePerformanceReviewServiceInterface $updateService,
        private readonly DeletePerformanceReviewServiceInterface $deleteService,
    ) {
    }

    public function index(ListPerformanceReviewRequest $request): JsonResponse
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
            'data' => PerformanceReviewResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PerformanceReviewResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PerformanceReviewResource($result->valueOrFail());
    }

    public function store(UpsertPerformanceReviewRequest $request): JsonResponse|PerformanceReviewResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PerformanceReviewResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPerformanceReviewRequest $request, int|string $id): JsonResponse|PerformanceReviewResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PerformanceReviewResource($result->valueOrFail());
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
