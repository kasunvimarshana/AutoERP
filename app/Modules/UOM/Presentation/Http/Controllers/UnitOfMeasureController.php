<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UOM\Application\DTOs\UnitOfMeasureData;
use Modules\UOM\Application\Services\UomService;
use Modules\UOM\Domain\Exceptions\UomIntegrityException;
use Modules\UOM\Domain\Exceptions\UomRecordNotFoundException;
use Modules\UOM\Presentation\Http\Controllers\Concerns\HandlesUomHttp;
use Modules\UOM\Presentation\Http\Requests\StoreUnitOfMeasureRequest;
use Modules\UOM\Presentation\Http\Requests\UpdateUnitOfMeasureRequest;
use Modules\UOM\Presentation\Http\Resources\UnitOfMeasureResource;

class UnitOfMeasureController extends Controller
{
    use HandlesUomHttp;

    public function __construct(private readonly UomService $uoms) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return UnitOfMeasureResource::collection(
                $this->uoms->listUnits($tenant, $this->filters($request, ['organization_unit_id', 'type', 'is_base']), $this->perPage($request))
            );
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreUnitOfMeasureRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $record = $this->uoms->createUnit(UnitOfMeasureData::fromArray($tenant, $request->validated()));

            return (new UnitOfMeasureResource($record))->response()->setStatusCode(201);
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $unit): UnitOfMeasureResource|JsonResponse
    {
        try {
            return new UnitOfMeasureResource($this->uoms->findUnit($tenant, $unit));
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUnitOfMeasureRequest $request, int|string $tenant, int|string $unit): UnitOfMeasureResource|JsonResponse
    {
        try {
            return new UnitOfMeasureResource($this->uoms->updateUnit($tenant, $unit, UnitOfMeasureData::fromArray($tenant, $request->validated())));
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $unit): JsonResponse
    {
        try {
            $this->uoms->deleteUnit($tenant, $unit);

            return response()->json(null, 204);
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
