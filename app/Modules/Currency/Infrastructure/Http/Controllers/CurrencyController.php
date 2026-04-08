<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Currency\Application\Contracts\CurrencyServiceInterface;
use Modules\Currency\Infrastructure\Http\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyServiceInterface $currencyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $activeOnly = filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN);

        $currencies = $activeOnly
            ? $this->currencyService->getActiveCurrencies($tenantId)
            : $this->currencyService->getAllCurrencies($tenantId);

        return response()->json(CurrencyResource::collection(collect($currencies)));
    }

    public function show(Request $request, string $currency): JsonResponse
    {
        $result = $this->currencyService->getCurrency($request->user()->tenant_id, $currency);

        return response()->json(new CurrencyResource($result));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'           => 'required|string|size:3',
            'name'           => 'required|string|max:100',
            'symbol'         => 'required|string|max:10',
            'decimal_places' => 'sometimes|integer|min:0|max:8',
            'is_base'        => 'sometimes|boolean',
            'is_active'      => 'sometimes|boolean',
        ]);

        $currency = $this->currencyService->createCurrency($request->user()->tenant_id, $data);

        return response()->json(new CurrencyResource($currency), 201);
    }

    public function update(Request $request, string $currency): JsonResponse
    {
        $data = $request->validate([
            'code'           => 'sometimes|string|size:3',
            'name'           => 'sometimes|string|max:100',
            'symbol'         => 'sometimes|string|max:10',
            'decimal_places' => 'sometimes|integer|min:0|max:8',
            'is_base'        => 'sometimes|boolean',
            'is_active'      => 'sometimes|boolean',
        ]);

        $result = $this->currencyService->updateCurrency($request->user()->tenant_id, $currency, $data);

        return response()->json(new CurrencyResource($result));
    }

    public function destroy(Request $request, string $currency): JsonResponse
    {
        $this->currencyService->deleteCurrency($request->user()->tenant_id, $currency);

        return response()->json(null, 204);
    }
}
