<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Currency\Application\Contracts\ConvertAmountServiceInterface;
use Modules\Currency\Application\Contracts\ExchangeRateServiceInterface;
use Modules\Currency\Infrastructure\Http\Resources\ExchangeRateResource;

class ExchangeRateController extends Controller
{
    public function __construct(
        private readonly ExchangeRateServiceInterface $exchangeRateService,
        private readonly ConvertAmountServiceInterface $convertAmountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rates = $this->exchangeRateService->getAllExchangeRates($request->user()->tenant_id);

        return response()->json(ExchangeRateResource::collection(collect($rates)));
    }

    public function show(Request $request, string $exchangeRate): JsonResponse
    {
        $rate = $this->exchangeRateService->getExchangeRate($request->user()->tenant_id, $exchangeRate);

        return response()->json(new ExchangeRateResource($rate));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_currency'  => 'required|string|size:3',
            'to_currency'    => 'required|string|size:3',
            'rate'           => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'source'         => 'sometimes|in:manual,api,import',
        ]);

        $rate = $this->exchangeRateService->createExchangeRate($request->user()->tenant_id, $data);

        return response()->json(new ExchangeRateResource($rate), 201);
    }

    public function update(Request $request, string $exchangeRate): JsonResponse
    {
        $data = $request->validate([
            'from_currency'  => 'sometimes|string|size:3',
            'to_currency'    => 'sometimes|string|size:3',
            'rate'           => 'sometimes|numeric|min:0',
            'effective_date' => 'sometimes|date',
            'source'         => 'sometimes|in:manual,api,import',
        ]);

        $rate = $this->exchangeRateService->updateExchangeRate($request->user()->tenant_id, $exchangeRate, $data);

        return response()->json(new ExchangeRateResource($rate));
    }

    public function destroy(Request $request, string $exchangeRate): JsonResponse
    {
        $this->exchangeRateService->deleteExchangeRate($request->user()->tenant_id, $exchangeRate);

        return response()->json(null, 204);
    }

    public function convert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'from'   => 'required|string|size:3',
            'to'     => 'required|string|size:3',
            'date'   => 'nullable|date',
        ]);

        $converted = $this->convertAmountService->convert(
            $request->user()->tenant_id,
            (float) $data['amount'],
            $data['from'],
            $data['to'],
            $data['date'] ?? null,
        );

        return response()->json([
            'amount'    => $data['amount'],
            'from'      => strtoupper($data['from']),
            'to'        => strtoupper($data['to']),
            'converted' => $converted,
        ]);
    }
}
