<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Requests\StoreTaxRateRequest;
use Modules\Pricing\Resources\TaxRateResource;
use Modules\Pricing\Services\TaxRateService;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateService $taxRateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $taxRates = $this->taxRateService->getAll($filters);

        return $this->successResponse(
            TaxRateResource::collection($taxRates),
            'Tax rates retrieved successfully'
        );
    }

    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $taxRate = $this->taxRateService->create($request->validated());

        return $this->createdResponse(
            new TaxRateResource($taxRate),
            'Tax rate created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->getById($id);

        return $this->successResponse(
            new TaxRateResource($taxRate),
            'Tax rate retrieved successfully'
        );
    }

    public function update(StoreTaxRateRequest $request, int $id): JsonResponse
    {
        $taxRate = $this->taxRateService->update($id, $request->validated());

        return $this->successResponse(
            new TaxRateResource($taxRate),
            'Tax rate updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->taxRateService->delete($id);

        return $this->successResponse(null, 'Tax rate deleted successfully');
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'jurisdiction' => 'required|string',
            'product_category' => 'nullable|string',
            'inclusive' => 'sometimes|boolean',
        ]);

        $result = $this->taxRateService->calculateTax(
            (string) $request->input('amount'),
            $request->input('jurisdiction'),
            $request->input('product_category'),
            $request->boolean('inclusive', false)
        );

        return $this->successResponse($result, 'Tax calculated successfully');
    }
}
