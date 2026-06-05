<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Supplier\Application\Services\SupplierService;
use Modules\Supplier\Presentation\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierRequest;
use Modules\Supplier\Presentation\Http\Resources\SupplierListResource;
use Modules\Supplier\Presentation\Http\Resources\SupplierResource;

final class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers) {}

    public function index(ListSupplierRequest $request): AnonymousResourceCollection
    {
        return SupplierListResource::collection($this->suppliers->paginate($request->validated()));
    }

    public function show(int $supplier): SupplierResource
    {
        return new SupplierResource($this->suppliers->find($supplier));
    }

    public function store(UpsertSupplierRequest $request): JsonResponse
    {
        return (new SupplierResource($this->suppliers->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpsertSupplierRequest $request, int $supplier): SupplierResource
    {
        return new SupplierResource($this->suppliers->update($supplier, $request->validated()));
    }

    public function destroy(int $supplier): JsonResponse
    {
        $this->suppliers->delete($supplier);

        return response()->json(null, 204);
    }
}
