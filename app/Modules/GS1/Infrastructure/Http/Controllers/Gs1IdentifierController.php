<?php

declare(strict_types=1);

namespace Modules\GS1\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\GS1\Application\Contracts\CreateGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\DeleteGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\FindGs1IdentifierServiceInterface;
use Modules\GS1\Application\Contracts\UpdateGs1IdentifierServiceInterface;
use Modules\GS1\Application\DTOs\Gs1IdentifierData;
use Modules\GS1\Application\DTOs\UpdateGs1IdentifierData;
use Modules\GS1\Infrastructure\Http\Requests\StoreGs1IdentifierRequest;
use Modules\GS1\Infrastructure\Http\Requests\UpdateGs1IdentifierRequest;
use Modules\GS1\Infrastructure\Http\Resources\Gs1IdentifierCollection;
use Modules\GS1\Infrastructure\Http\Resources\Gs1IdentifierResource;

class Gs1IdentifierController extends AuthorizedController
{
    public function __construct(
        protected FindGs1IdentifierServiceInterface $findService,
        protected CreateGs1IdentifierServiceInterface $createService,
        protected UpdateGs1IdentifierServiceInterface $updateService,
        protected DeleteGs1IdentifierServiceInterface $deleteService,
    ) {}

    public function index(Request $request): Gs1IdentifierCollection
    {
        $filters = $request->only(['tenant_id', 'identifier_type', 'entity_type', 'entity_id', 'is_active']);

        return new Gs1IdentifierCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreGs1IdentifierRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = Gs1IdentifierData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'identifierType'  => $v['identifier_type'],
            'identifierValue' => $v['identifier_value'],
            'entityType'      => $v['entity_type'] ?? null,
            'entityId'        => $v['entity_id'] ?? null,
            'isActive'        => $v['is_active'] ?? true,
            'metadata'        => $v['metadata'] ?? null,
        ]);

        $identifier = $this->createService->execute($dto->toArray());

        return (new Gs1IdentifierResource($identifier))->response()->setStatusCode(201);
    }

    public function show(int $id): Gs1IdentifierResource|JsonResponse
    {
        $identifier = $this->findService->find($id);
        if (! $identifier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new Gs1IdentifierResource($identifier);
    }

    public function update(UpdateGs1IdentifierRequest $request, int $id): Gs1IdentifierResource
    {
        $v   = $request->validated();
        $dto = UpdateGs1IdentifierData::fromArray(array_merge(['id' => $id], $v));

        return new Gs1IdentifierResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'GS1 identifier deleted successfully']);
    }
}
