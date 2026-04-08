<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateTrainingServiceInterface;
use Modules\HR\Application\Contracts\DeleteTrainingServiceInterface;
use Modules\HR\Application\Contracts\FindTrainingServiceInterface;
use Modules\HR\Application\Contracts\UpdateTrainingServiceInterface;
use Modules\HR\Application\DTOs\TrainingData;
use Modules\HR\Application\DTOs\UpdateTrainingData;
use Modules\HR\Domain\Entities\Training;
use Modules\HR\Infrastructure\Http\Requests\StoreTrainingRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateTrainingRequest;
use Modules\HR\Infrastructure\Http\Resources\TrainingCollection;
use Modules\HR\Infrastructure\Http\Resources\TrainingResource;
use OpenApi\Attributes as OA;

class TrainingController extends AuthorizedController
{
    public function __construct(
        protected FindTrainingServiceInterface $findService,
        protected CreateTrainingServiceInterface $createService,
        protected UpdateTrainingServiceInterface $updateService,
        protected DeleteTrainingServiceInterface $deleteService,
    ) {}

    #[OA\Get(
        path: '/api/hr/training',
        summary: 'List training programs',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status',   in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'sort',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of training programs'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): TrainingCollection
    {
        $this->authorize('viewAny', Training::class);
        $filters = $request->only(['status']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');
        $records = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new TrainingCollection($records);
    }

    #[OA\Post(
        path: '/api/hr/training',
        summary: 'Create training program',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['tenant_id', 'title', 'start_date'],
            properties: [
                new OA\Property(property: 'tenant_id',        type: 'integer'),
                new OA\Property(property: 'title',            type: 'string'),
                new OA\Property(property: 'start_date',       type: 'string', format: 'date'),
                new OA\Property(property: 'description',      type: 'string', nullable: true),
                new OA\Property(property: 'trainer',          type: 'string', nullable: true),
                new OA\Property(property: 'location',         type: 'string', nullable: true),
                new OA\Property(property: 'end_date',         type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'max_participants', type: 'integer', nullable: true),
                new OA\Property(property: 'status',           type: 'string', enum: ['scheduled', 'in_progress', 'completed', 'cancelled']),
                new OA\Property(property: 'is_active',        type: 'boolean', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Training program created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $this->authorize('create', Training::class);
        $dto      = TrainingData::fromArray($request->validated());
        $training = $this->createService->execute($dto->toArray());

        return (new TrainingResource($training))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/hr/training/{id}',
        summary: 'Get training program',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Training program details'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(int $id): TrainingResource
    {
        $training = $this->findService->find($id);
        if (! $training) {
            abort(404);
        }
        $this->authorize('view', $training);

        return new TrainingResource($training);
    }

    #[OA\Put(
        path: '/api/hr/training/{id}',
        summary: 'Update training program',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'title',            type: 'string'),
            new OA\Property(property: 'start_date',       type: 'string', format: 'date'),
            new OA\Property(property: 'description',      type: 'string', nullable: true),
            new OA\Property(property: 'trainer',          type: 'string', nullable: true),
            new OA\Property(property: 'location',         type: 'string', nullable: true),
            new OA\Property(property: 'end_date',         type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'max_participants', type: 'integer', nullable: true),
            new OA\Property(property: 'is_active',        type: 'boolean', nullable: true),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Updated training program'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function update(UpdateTrainingRequest $request, int $id): TrainingResource
    {
        $training = $this->findService->find($id);
        if (! $training) {
            abort(404);
        }
        $this->authorize('update', $training);
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateTrainingData::fromArray($validated);
        $updated         = $this->updateService->execute($dto->toArray());

        return new TrainingResource($updated);
    }

    #[OA\Delete(
        path: '/api/hr/training/{id}',
        summary: 'Delete training program',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $training = $this->findService->find($id);
        if (! $training) {
            abort(404);
        }
        $this->authorize('delete', $training);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Training program deleted successfully']);
    }

    #[OA\Get(
        path: '/api/hr/training/by-status/{status}',
        summary: 'Get training programs by status',
        tags: ['HR - Training'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'status', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Training programs with the given status')],
    )]
    public function byStatus(string $status): JsonResponse
    {
        $this->authorize('viewAny', Training::class);
        $items = $this->findService->getByStatus($status);

        return response()->json(['data' => TrainingResource::collection(collect($items))]);
    }
}
