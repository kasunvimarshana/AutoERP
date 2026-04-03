<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\FindUomConversionServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Application\DTOs\UpdateUomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Infrastructure\Http\Requests\StoreUomConversionRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUomConversionRequest;
use Modules\UoM\Infrastructure\Http\Resources\UomConversionCollection;
use Modules\UoM\Infrastructure\Http\Resources\UomConversionResource;

class UomConversionController extends AuthorizedController
{
    public function __construct(
        protected FindUomConversionServiceInterface $findService,
        protected CreateUomConversionServiceInterface $createService,
        protected UpdateUomConversionServiceInterface $updateService,
        protected DeleteUomConversionServiceInterface $deleteService,
    ) {}

    public function index(Request $request): UomConversionCollection
    {
        $this->authorize('viewAny', UomConversion::class);
        $filters = $request->only(['tenant_id', 'from_uom_id', 'to_uom_id', 'is_active']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $conversions = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new UomConversionCollection($conversions);
    }

    public function store(StoreUomConversionRequest $request): JsonResponse
    {
        $this->authorize('create', UomConversion::class);
        $validated = $request->validated();

        $dto = UomConversionData::fromArray([
            'tenantId'  => $validated['tenant_id'],
            'fromUomId' => $validated['from_uom_id'],
            'toUomId'   => $validated['to_uom_id'],
            'factor'    => $validated['factor'],
            'isActive'  => $validated['is_active'] ?? true,
        ]);

        $conversion = $this->createService->execute($dto->toArray());

        return (new UomConversionResource($conversion))->response()->setStatusCode(201);
    }

    public function show(int $id): UomConversionResource
    {
        $conversion = $this->findService->find($id);
        if (! $conversion) {
            abort(404);
        }
        $this->authorize('view', $conversion);

        return new UomConversionResource($conversion);
    }

    public function update(UpdateUomConversionRequest $request, int $id): UomConversionResource
    {
        $conversion = $this->findService->find($id);
        if (! $conversion) {
            abort(404);
        }
        $this->authorize('update', $conversion);

        $validated = $request->validated();
        $payload   = ['id' => $id];

        if (array_key_exists('from_uom_id', $validated)) {
            $payload['fromUomId'] = $validated['from_uom_id'];
        }
        if (array_key_exists('to_uom_id', $validated)) {
            $payload['toUomId'] = $validated['to_uom_id'];
        }
        if (array_key_exists('factor', $validated)) {
            $payload['factor'] = $validated['factor'];
        }
        if (array_key_exists('is_active', $validated)) {
            $payload['isActive'] = $validated['is_active'];
        }

        $dto     = UpdateUomConversionData::fromArray($payload);
        $updated = $this->updateService->execute($dto->toArray() + ['id' => $id]);

        return new UomConversionResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $conversion = $this->findService->find($id);
        if (! $conversion) {
            abort(404);
        }
        $this->authorize('delete', $conversion);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'UoM conversion deleted successfully']);
    }
}
