<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Taxation\StoreTaxExemptionRequest;
use App\Http\Requests\Taxation\UpdateTaxExemptionRequest;
use App\Http\Resources\Taxation\TaxExemptionResource;
use App\Modules\Taxation\Services\TaxExemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxExemptionController extends BaseController
{
    public function __construct(
        private readonly TaxExemptionService $taxExemptionService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $taxExemptions = $this->taxExemptionService->getAll([], $perPage);

        return TaxExemptionResource::collection($taxExemptions);
    }

    public function store(StoreTaxExemptionRequest $request): JsonResponse
    {
        try {
            $taxExemption = $this->taxExemptionService->create($request->validated());

            return $this->successResponse(
                new TaxExemptionResource($taxExemption),
                'Tax exemption created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create tax exemption',
                [$e->getMessage()],
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $taxExemption = $this->taxExemptionService->getById($id);

            return $this->successResponse(new TaxExemptionResource($taxExemption));
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Tax exemption not found',
                [$e->getMessage()],
                404
            );
        }
    }

    public function update(UpdateTaxExemptionRequest $request, int $id): JsonResponse
    {
        try {
            $taxExemption = $this->taxExemptionService->update($id, $request->validated());

            return $this->successResponse(
                new TaxExemptionResource($taxExemption),
                'Tax exemption updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update tax exemption',
                [$e->getMessage()],
                500
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->taxExemptionService->delete($id);

            return $this->successResponse(
                null,
                'Tax exemption deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete tax exemption',
                [$e->getMessage()],
                500
            );
        }
    }

    public function active(): AnonymousResourceCollection
    {
        $taxExemptions = $this->taxExemptionService->getActiveExemptions();
        return TaxExemptionResource::collection($taxExemptions);
    }

    public function byEntity(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'entity_type' => ['required', 'in:customer,product,product_category,vendor'],
            'entity_id' => ['required', 'integer'],
        ]);

        $taxExemptions = $this->taxExemptionService->findByEntity(
            $request->input('entity_type'),
            $request->input('entity_id')
        );

        return TaxExemptionResource::collection($taxExemptions);
    }
}
