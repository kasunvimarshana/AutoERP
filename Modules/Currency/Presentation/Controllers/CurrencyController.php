<?php

namespace Modules\Currency\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Currency\Application\UseCases\CreateCurrencyUseCase;
use Modules\Currency\Application\UseCases\DeactivateCurrencyUseCase;
use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Presentation\Requests\StoreCurrencyRequest;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyRepositoryInterface $currencyRepo,
        private CreateCurrencyUseCase       $createUseCase,
        private DeactivateCurrencyUseCase   $deactivateUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $page     = (int) request()->get('page', 1);

        return response()->json($this->currencyRepo->findByTenant($tenantId, $page));
    }

    public function active(): JsonResponse
    {
        return response()->json(
            $this->currencyRepo->findActiveByTenant(auth()->user()?->tenant_id)
        );
    }

    public function store(StoreCurrencyRequest $request): JsonResponse
    {
        $currency = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($currency, 201);
    }

    public function show(string $id): JsonResponse
    {
        $currency = $this->currencyRepo->findById($id);

        if (! $currency) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($currency);
    }

    public function update(StoreCurrencyRequest $request, string $id): JsonResponse
    {
        $currency = $this->currencyRepo->update($id, $request->validated());

        return response()->json($currency);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->currencyRepo->delete($id);

        return response()->json(null, 204);
    }

    public function deactivate(string $id): JsonResponse
    {
        $currency = $this->deactivateUseCase->execute($id);

        return response()->json($currency);
    }
}
