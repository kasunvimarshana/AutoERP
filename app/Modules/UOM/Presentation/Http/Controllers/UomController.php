<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\UOM\Application\Services\UomService;
use Modules\UOM\Presentation\Http\Requests\ListUomRequest;
use Modules\UOM\Presentation\Http\Requests\UpsertUomRequest;
use Modules\UOM\Presentation\Http\Resources\UomListResource;
use Modules\UOM\Presentation\Http\Resources\UomLookupResource;
use Modules\UOM\Presentation\Http\Resources\UomResource;

final class UomController extends Controller
{
    public function __construct(private readonly UomService $uoms) {}

    public function index(ListUomRequest $request): AnonymousResourceCollection
    {
        return UomListResource::collection($this->uoms->paginate($request->validated()));
    }

    public function lookup(): AnonymousResourceCollection
    {
        return UomLookupResource::collection($this->uoms->lookup());
    }

    public function show(int $uom): UomResource
    {
        return new UomResource($this->uoms->find($uom));
    }

    public function store(UpsertUomRequest $request): JsonResponse
    {
        return (new UomResource($this->uoms->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpsertUomRequest $request, int $uom): UomResource
    {
        return new UomResource($this->uoms->update($uom, $request->validated()));
    }

    public function destroy(int $uom): JsonResponse
    {
        $this->uoms->delete($uom);

        return response()->json(null, 204);
    }
}
