<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tax\Application\Contracts\CalculateTaxServiceInterface;
use Modules\Tax\Application\Contracts\TaxGroupServiceInterface;
use Modules\Tax\Infrastructure\Http\Resources\TaxGroupResource;

class TaxGroupController extends Controller
{
    public function __construct(
        private readonly TaxGroupServiceInterface $taxGroupService,
        private readonly CalculateTaxServiceInterface $calculateTaxService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $groups = $this->taxGroupService->getAllTaxGroups($request->user()->tenant_id);

        return response()->json(TaxGroupResource::collection(collect($groups)));
    }

    public function show(Request $request, string $taxGroup): JsonResponse
    {
        $group = $this->taxGroupService->getTaxGroup($request->user()->tenant_id, $taxGroup);

        return response()->json(new TaxGroupResource($group));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_compound' => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
        ]);

        $group = $this->taxGroupService->createTaxGroup($request->user()->tenant_id, $data);

        return response()->json(new TaxGroupResource($group), 201);
    }

    public function update(Request $request, string $taxGroup): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'code'        => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'is_compound' => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
        ]);

        $group = $this->taxGroupService->updateTaxGroup($request->user()->tenant_id, $taxGroup, $data);

        return response()->json(new TaxGroupResource($group));
    }

    public function destroy(Request $request, string $taxGroup): JsonResponse
    {
        $this->taxGroupService->deleteTaxGroup($request->user()->tenant_id, $taxGroup);

        return response()->json(null, 204);
    }

    public function calculate(Request $request, string $taxGroup): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $result = $this->calculateTaxService->calculate(
            $request->user()->tenant_id,
            $taxGroup,
            (float) $data['amount'],
        );

        return response()->json($result);
    }
}
