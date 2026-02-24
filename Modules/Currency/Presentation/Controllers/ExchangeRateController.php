<?php

namespace Modules\Currency\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Currency\Application\UseCases\RecordExchangeRateUseCase;
use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;
use Modules\Currency\Presentation\Requests\StoreExchangeRateRequest;

class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateRepositoryInterface $exchangeRateRepo,
        private RecordExchangeRateUseCase       $recordUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $page     = (int) request()->get('page', 1);

        return response()->json($this->exchangeRateRepo->findByTenant($tenantId, $page));
    }

    public function store(StoreExchangeRateRequest $request): JsonResponse
    {
        $exchangeRate = $this->recordUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($exchangeRate, 201);
    }

    public function show(string $id): JsonResponse
    {
        $exchangeRate = $this->exchangeRateRepo->findById($id);

        if (! $exchangeRate) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($exchangeRate);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->exchangeRateRepo->delete($id);

        return response()->json(null, 204);
    }
}
