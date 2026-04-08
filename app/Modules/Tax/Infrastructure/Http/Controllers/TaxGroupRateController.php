<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tax\Application\Contracts\TaxGroupRateServiceInterface;
use Modules\Tax\Infrastructure\Http\Resources\TaxGroupRateResource;

class TaxGroupRateController extends Controller
{
    public function __construct(
        private readonly TaxGroupRateServiceInterface $taxGroupRateService,
    ) {}

    public function index(Request $request, string $taxGroup): JsonResponse
    {
        $rates = $this->taxGroupRateService->getRatesForGroup($request->user()->tenant_id, $taxGroup);

        return response()->json(TaxGroupRateResource::collection(collect($rates)));
    }

    public function show(Request $request, string $taxGroup, string $taxGroupRate): JsonResponse
    {
        $rate = $this->taxGroupRateService->getTaxGroupRate($request->user()->tenant_id, $taxGroupRate);

        return response()->json(new TaxGroupRateResource($rate));
    }

    public function store(Request $request, string $taxGroup): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'rate'      => 'required|numeric|min:0',
            'type'      => 'sometimes|in:percentage,fixed',
            'sequence'  => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['tax_group_id'] = $taxGroup;

        $rate = $this->taxGroupRateService->createTaxGroupRate($request->user()->tenant_id, $data);

        return response()->json(new TaxGroupRateResource($rate), 201);
    }

    public function update(Request $request, string $taxGroup, string $taxGroupRate): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'rate'      => 'sometimes|numeric|min:0',
            'type'      => 'sometimes|in:percentage,fixed',
            'sequence'  => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $rate = $this->taxGroupRateService->updateTaxGroupRate($request->user()->tenant_id, $taxGroupRate, $data);

        return response()->json(new TaxGroupRateResource($rate));
    }

    public function destroy(Request $request, string $taxGroup, string $taxGroupRate): JsonResponse
    {
        $this->taxGroupRateService->deleteTaxGroupRate($request->user()->tenant_id, $taxGroupRate);

        return response()->json(null, 204);
    }
}
