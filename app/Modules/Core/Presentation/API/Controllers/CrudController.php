<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\API\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Application\Services\CrudOrchestratorService;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Core\Presentation\API\Requests\ListResourceRequest;
use Modules\Core\Presentation\API\Requests\StoreResourceRequest;
use Modules\Core\Presentation\API\Requests\UpdateResourceRequest;
use Modules\Core\Presentation\API\Resources\CrudResource;
use Symfony\Component\HttpFoundation\Response;

class CrudController extends AuthorizedController
{
    public function __construct(
        protected readonly CrudOrchestratorService $orchestrator,
    ) {}

    public function index(ListResourceRequest $request): JsonResponse
    {
        $result = $this->orchestrator->list($request->toDto());

        return response()->json($result);
    }

    public function store(StoreResourceRequest $request): JsonResponse
    {
        $record = $this->orchestrator->create($request->toDto());

        return (new CrudResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int|string $id): JsonResponse
    {
        $record = $this->orchestrator->find($id);

        return (new CrudResource($record))->response();
    }

    public function update(UpdateResourceRequest $request, int|string $id): JsonResponse
    {
        $record = $this->orchestrator->update($request->toDto($id));

        return (new CrudResource($record))->response();
    }

    public function destroy(int|string $id): JsonResponse
    {
        $this->orchestrator->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
