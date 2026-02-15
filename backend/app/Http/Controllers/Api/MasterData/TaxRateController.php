<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\BaseController;
use App\Http\Resources\TaxRateResource;
use App\Modules\MasterData\Repositories\TaxRateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxRateController extends BaseController
{
    public function __construct(
        private readonly TaxRateRepository $taxRateRepository
    ) {}

    /**
     * Display a listing of tax rates.
     */
    public function index(): AnonymousResourceCollection
    {
        $taxRates = $this->taxRateRepository->all();
        return TaxRateResource::collection($taxRates);
    }

    /**
     * Store a newly created tax rate.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:tax_rates,code'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', 'in:percentage,fixed'],
            'is_compound' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $taxRate = $this->taxRateRepository->create($validated);

            return $this->successResponse(
                new TaxRateResource($taxRate),
                'Tax rate created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create tax rate',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified tax rate.
     */
    public function show(int $id): JsonResponse
    {
        $taxRate = $this->taxRateRepository->find($id);

        if (!$taxRate) {
            return $this->errorResponse('Tax rate not found', [], 404);
        }

        return $this->successResponse(new TaxRateResource($taxRate));
    }

    /**
     * Update the specified tax rate.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $taxRate = $this->taxRateRepository->find($id);

        if (!$taxRate) {
            return $this->errorResponse('Tax rate not found', [], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:tax_rates,code,' . $id],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'type' => ['sometimes', 'required', 'in:percentage,fixed'],
            'is_compound' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after:valid_from'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $taxRate = $this->taxRateRepository->update($taxRate, $validated);

            return $this->successResponse(
                new TaxRateResource($taxRate),
                'Tax rate updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update tax rate',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified tax rate.
     */
    public function destroy(int $id): JsonResponse
    {
        $taxRate = $this->taxRateRepository->find($id);

        if (!$taxRate) {
            return $this->errorResponse('Tax rate not found', [], 404);
        }

        try {
            $this->taxRateRepository->delete($taxRate);

            return $this->successResponse(
                null,
                'Tax rate deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete tax rate',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Get active tax rates.
     */
    public function active(): AnonymousResourceCollection
    {
        $taxRates = $this->taxRateRepository->getActiveTaxRates();
        return TaxRateResource::collection($taxRates);
    }

    /**
     * Get valid tax rates for a specific date.
     */
    public function validOn(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $taxRates = $this->taxRateRepository->getValidTaxRates($request->date);
        return TaxRateResource::collection($taxRates);
    }
}
