<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Infrastructure\Http\Requests\StorePerformanceReviewRequest;
use Modules\HR\Infrastructure\Http\Requests\SubmitPerformanceReviewRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePerformanceReviewRequest;
use Modules\HR\Infrastructure\Http\Resources\PerformanceReviewResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PerformanceReviewController extends AuthorizedController
{
    public function __construct(
        protected CreatePerformanceReviewServiceInterface $createService,
        protected UpdatePerformanceReviewServiceInterface $updateService,
        protected FindPerformanceReviewServiceInterface $findService,
        protected SubmitPerformanceReviewServiceInterface $submitService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => PerformanceReviewResource::collection($result)]);
    }

    public function store(StorePerformanceReviewRequest $request): JsonResponse
    {
        $entity = $this->createService->execute($request->validated());

        return (new PerformanceReviewResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $performanceReview): PerformanceReviewResource
    {
        return new PerformanceReviewResource($this->findOrFail($performanceReview));
    }

    public function update(UpdatePerformanceReviewRequest $request, int $performanceReview): PerformanceReviewResource
    {
        $this->findOrFail($performanceReview);
        $payload = $request->validated();
        $payload['id'] = $performanceReview;
        $updated = $this->updateService->execute($payload);

        return new PerformanceReviewResource($updated);
    }

    public function destroy(int $performanceReview): JsonResponse
    {
        $this->findOrFail($performanceReview);

        return Response::json(null, 204);
    }

    public function submit(SubmitPerformanceReviewRequest $request, int $performanceReview): PerformanceReviewResource
    {
        $this->findOrFail($performanceReview);
        $updated = $this->submitService->execute(['id' => $performanceReview]);

        return new PerformanceReviewResource($updated);
    }

    private function findOrFail(int $id): PerformanceReview
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Performance review not found.');
        }

        return $entity;
    }
}
