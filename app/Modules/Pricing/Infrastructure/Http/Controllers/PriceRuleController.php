<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Contracts\PriceRuleServiceInterface;
use Modules\Pricing\Infrastructure\Http\Resources\PriceRuleResource;

class PriceRuleController extends Controller
{
    public function __construct(
        private readonly PriceRuleServiceInterface $priceRuleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(PriceRuleResource::collection($this->priceRuleService->getRulesForPriceList($tenantId, $request->query('price_list_id', ''))));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $priceRule = $this->priceRuleService->createPriceRule($tenantId, $request->all());
        return response()->json(new PriceRuleResource($priceRule), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new PriceRuleResource($this->priceRuleService->getPriceRule($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new PriceRuleResource($this->priceRuleService->updatePriceRule($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->priceRuleService->deletePriceRule($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }
}
