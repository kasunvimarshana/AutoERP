<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\CRM\Application\Contracts\SupplierServiceInterface;
use Modules\CRM\Application\DTOs\SupplierData;
use Modules\CRM\Infrastructure\Http\Resources\SupplierResource;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class SupplierController extends BaseController
{
    public function __construct(SupplierServiceInterface $service)
    {
        parent::__construct($service, SupplierResource::class, SupplierData::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return SupplierModel::class;
    }

    /**
     * List suppliers with optional filters.
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'type']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
            $request->input('include'),
        );

        return SupplierResource::collection($paginator);
    }

    /**
     * Create a new supplier.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\CRM\Application\Contracts\SupplierServiceInterface $service */
        $service = $this->service;
        $supplier = $service->createSupplier($request->all());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single supplier.
     */
    public function show(string $id): JsonResponse
    {
        $supplier = $this->service->find($id);

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Update a supplier.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\CRM\Application\Contracts\SupplierServiceInterface $service */
        $service = $this->service;
        $supplier = $service->updateSupplier($id, $request->all());

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Delete a supplier (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
