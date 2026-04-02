<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\DeletePerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\SubmitPerformanceReviewServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceReviewServiceInterface;
use Modules\HR\Application\DTOs\PerformanceReviewData;
use Modules\HR\Application\DTOs\UpdatePerformanceReviewData;
use Modules\HR\Domain\Entities\PerformanceReview;
use Modules\HR\Infrastructure\Http\Requests\StorePerformanceReviewRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePerformanceReviewRequest;
use Modules\HR\Infrastructure\Http\Resources\PerformanceReviewCollection;
use Modules\HR\Infrastructure\Http\Resources\PerformanceReviewResource;
use OpenApi\Attributes as OA;

class PerformanceReviewController extends AuthorizedController
{
    public function __construct(
        protected FindPerformanceReviewServiceInterface $findService,
        protected CreatePerformanceReviewServiceInterface $createService,
        protected UpdatePerformanceReviewServiceInterface $updateService,
        protected DeletePerformanceReviewServiceInterface $deleteService,
        protected SubmitPerformanceReviewServiceInterface $submitService,
    ) {}

    #[OA\Get(
        path: '/api/hr/performance-reviews',
        summary: 'List performance reviews',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'employee_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'reviewer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status',      in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page',    in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',        in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',        in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of performance reviews'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): PerformanceReviewCollection
    {
        $this->authorize('viewAny', PerformanceReview::class);
        $filters = $request->only(['employee_id', 'reviewer_id', 'status']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $records = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new PerformanceReviewCollection($records);
    }

    #[OA\Post(
        path: '/api/hr/performance-reviews',
        summary: 'Create performance review',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'employee_id', 'reviewer_id', 'review_period_start', 'review_period_end', 'rating'],
            properties: [
                new OA\Property(property: 'tenant_id',           type: 'integer'),
                new OA\Property(property: 'employee_id',         type: 'integer'),
                new OA\Property(property: 'reviewer_id',         type: 'integer'),
                new OA\Property(property: 'review_period_start', type: 'string', format: 'date'),
                new OA\Property(property: 'review_period_end',   type: 'string', format: 'date'),
                new OA\Property(property: 'rating',              type: 'number', minimum: 1, maximum: 5),
                new OA\Property(property: 'comments',            type: 'string', nullable: true),
                new OA\Property(property: 'goals',               type: 'string', nullable: true),
                new OA\Property(property: 'achievements',        type: 'string', nullable: true),
                new OA\Property(property: 'status',              type: 'string', enum: ['draft', 'submitted', 'acknowledged']),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Performance review created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StorePerformanceReviewRequest $request): JsonResponse
    {
        $this->authorize('create', PerformanceReview::class);
        $dto    = PerformanceReviewData::fromArray($request->validated());
        $review = $this->createService->execute($dto->toArray());

        return (new PerformanceReviewResource($review))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/performance-reviews/{id}',
        summary: 'Get performance review',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Performance review details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): PerformanceReviewResource
    {
        $review = $this->findService->find($id);
        if (! $review) {
            abort(404);
        }
        $this->authorize('view', $review);

        return new PerformanceReviewResource($review);
    }

    #[OA\Put(
        path: '/api/hr/performance-reviews/{id}',
        summary: 'Update performance review',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'review_period_start', type: 'string', format: 'date'),
            new OA\Property(property: 'review_period_end',   type: 'string', format: 'date'),
            new OA\Property(property: 'rating',              type: 'number', minimum: 1, maximum: 5),
            new OA\Property(property: 'comments',            type: 'string', nullable: true),
            new OA\Property(property: 'goals',               type: 'string', nullable: true),
            new OA\Property(property: 'achievements',        type: 'string', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated performance review'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdatePerformanceReviewRequest $request, int $id): PerformanceReviewResource
    {
        $review = $this->findService->find($id);
        if (! $review) {
            abort(404);
        }
        $this->authorize('update', $review);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdatePerformanceReviewData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new PerformanceReviewResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/performance-reviews/{id}',
        summary: 'Delete performance review',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $review = $this->findService->find($id);
        if (! $review) {
            abort(404);
        }
        $this->authorize('delete', $review);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Performance review deleted successfully']);
    }

    #[OA\Post(
        path: '/api/hr/performance-reviews/{id}/submit',
        summary: 'Submit performance review',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Performance review submitted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function submit(int $id): PerformanceReviewResource
    {
        $review = $this->findService->find($id);
        if (! $review) {
            abort(404);
        }
        $this->authorize('update', $review);
        $submitted = $this->submitService->execute(['id' => $id]);

        return new PerformanceReviewResource($submitted);
    }

    #[OA\Get(
        path: '/api/hr/performance-reviews/employee/{employeeId}',
        summary: 'Get performance reviews by employee',
        tags: ['HR - Performance Reviews'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'employeeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Performance reviews for the employee')],
    )]
    public function byEmployee(int $employeeId): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReview::class);
        $items = $this->findService->getByEmployee($employeeId);

        return response()->json(['data' => PerformanceReviewResource::collection(collect($items))]);
    }
}
