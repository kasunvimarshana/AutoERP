<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StorePerformanceReviewRequest;
use Modules\HR\Http\Requests\UpdatePerformanceReviewRequest;
use Modules\HR\Http\Resources\PerformanceReviewResource;
use Modules\HR\Repositories\Contracts\PerformanceReviewRepositoryInterface;

class PerformanceReviewController extends Controller
{
    public function __construct(
        protected PerformanceReviewRepositoryInterface $reviewRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $reviews = $this->reviewRepository->paginate($perPage);

        return PerformanceReviewResource::collection($reviews)->response();
    }

    public function store(StorePerformanceReviewRequest $request): JsonResponse
    {
        $review = $this->reviewRepository->create($request->validated());

        return (new PerformanceReviewResource($review))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        if (! $review) {
            return response()->json(['message' => 'Performance review not found'], 404);
        }

        return (new PerformanceReviewResource($review->load(['employee', 'reviewer'])))->response();
    }

    public function update(UpdatePerformanceReviewRequest $request, int $id): JsonResponse
    {
        $review = $this->reviewRepository->update($id, $request->validated());

        return (new PerformanceReviewResource($review))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->reviewRepository->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Performance review not found'], 404);
        }

        return response()->json(['message' => 'Performance review deleted successfully'], 200);
    }

    public function getByEmployee(int $employeeId): JsonResponse
    {
        $reviews = $this->reviewRepository->getByEmployee($employeeId);

        return PerformanceReviewResource::collection($reviews)->response();
    }
}
