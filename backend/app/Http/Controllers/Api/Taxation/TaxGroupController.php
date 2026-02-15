<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Taxation\StoreTaxGroupRequest;
use App\Http\Requests\Taxation\UpdateTaxGroupRequest;
use App\Http\Resources\Taxation\TaxGroupResource;
use App\Modules\Taxation\Services\TaxGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxGroupController extends BaseController
{
    public function __construct(
        private readonly TaxGroupService $taxGroupService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $taxGroups = $this->taxGroupService->getAll([], $perPage);

        return TaxGroupResource::collection($taxGroups);
    }

    public function store(StoreTaxGroupRequest $request): JsonResponse
    {
        try {
            $taxGroup = $this->taxGroupService->create($request->validated());

            if ($request->has('tax_rates')) {
                $taxRates = [];
                foreach ($request->input('tax_rates') as $taxRateData) {
                    $taxRates[$taxRateData['tax_rate_id']] = [
                        'sequence' => $taxRateData['sequence'],
                        'apply_on_previous' => $taxRateData['apply_on_previous'] ?? false,
                        'is_active' => true,
                    ];
                }
                $this->taxGroupService->syncTaxRates($taxGroup->id, $taxRates);
            }

            $taxGroup->load('taxRates');

            return $this->successResponse(
                new TaxGroupResource($taxGroup),
                'Tax group created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create tax group',
                [$e->getMessage()],
                500
            );
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $taxGroup = $this->taxGroupService->getById($id);
            $taxGroup->load('taxRates');

            return $this->successResponse(new TaxGroupResource($taxGroup));
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Tax group not found',
                [$e->getMessage()],
                404
            );
        }
    }

    public function update(UpdateTaxGroupRequest $request, int $id): JsonResponse
    {
        try {
            $taxGroup = $this->taxGroupService->update($id, $request->validated());
            $taxGroup->load('taxRates');

            return $this->successResponse(
                new TaxGroupResource($taxGroup),
                'Tax group updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update tax group',
                [$e->getMessage()],
                500
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->taxGroupService->delete($id);

            return $this->successResponse(
                null,
                'Tax group deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete tax group',
                [$e->getMessage()],
                500
            );
        }
    }

    public function active(): AnonymousResourceCollection
    {
        $taxGroups = $this->taxGroupService->getActiveTaxGroups();
        return TaxGroupResource::collection($taxGroups);
    }

    public function attachTaxRate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tax_rate_id' => ['required', 'exists:tax_rates,id'],
            'sequence' => ['required', 'integer', 'min:1'],
            'apply_on_previous' => ['boolean'],
        ]);

        try {
            $this->taxGroupService->attachTaxRate(
                $id,
                $request->input('tax_rate_id'),
                [
                    'sequence' => $request->input('sequence'),
                    'apply_on_previous' => $request->input('apply_on_previous', false),
                    'is_active' => true,
                ]
            );

            return $this->successResponse(
                null,
                'Tax rate attached to group successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to attach tax rate',
                [$e->getMessage()],
                500
            );
        }
    }

    public function detachTaxRate(int $id, int $taxRateId): JsonResponse
    {
        try {
            $this->taxGroupService->detachTaxRate($id, $taxRateId);

            return $this->successResponse(
                null,
                'Tax rate detached from group successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to detach tax rate',
                [$e->getMessage()],
                500
            );
        }
    }
}
