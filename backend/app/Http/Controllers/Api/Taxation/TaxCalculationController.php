<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Taxation\CalculateTaxRequest;
use App\Http\Resources\Taxation\TaxCalculationResource;
use App\Core\DTOs\Taxation\TaxCalculationRequestDTO;
use App\Modules\Taxation\Services\TaxationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxCalculationController extends BaseController
{
    public function __construct(
        private readonly TaxationService $taxationService
    ) {}

    public function calculate(CalculateTaxRequest $request): JsonResponse
    {
        try {
            $requestDTO = TaxCalculationRequestDTO::fromArray($request->validated());
            
            $result = $this->taxationService->calculateTax($requestDTO);

            return $this->successResponse(
                $result->toArray(),
                'Tax calculated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to calculate tax',
                [$e->getMessage()],
                500
            );
        }
    }

    public function calculateAndSave(CalculateTaxRequest $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
        ]);

        try {
            $requestDTO = TaxCalculationRequestDTO::fromArray($request->validated());
            
            $result = $this->taxationService->calculateTax($requestDTO);

            $this->taxationService->saveTaxCalculation(
                $request->input('entity_type'),
                $request->input('entity_id'),
                $result,
                $request->input('customer_id'),
                $request->input('product_id'),
                $request->input('branch_id')
            );

            return $this->successResponse(
                $result->toArray(),
                'Tax calculated and saved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to calculate and save tax',
                [$e->getMessage()],
                500
            );
        }
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'entity_type' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = array_filter([
            'entity_type' => $request->input('entity_type'),
            'customer_id' => $request->input('customer_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        $perPage = $request->input('per_page', 15);
        
        $calculations = $this->taxationService->repository->paginateCalculations($perPage, $filters);

        return TaxCalculationResource::collection($calculations);
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        try {
            $summary = $this->taxationService->getTaxSummary(
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->successResponse($summary, 'Tax summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve tax summary',
                [$e->getMessage()],
                500
            );
        }
    }

    public function breakdown(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ]);

        $breakdown = $this->taxationService->getTaxBreakdown(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return TaxCalculationResource::collection($breakdown);
    }
}
