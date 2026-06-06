<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Http\Requests\ListCurrencyRequest;
use Modules\Configuration\Http\Requests\UpsertCurrencyRequest;
use Modules\Configuration\Http\Resources\CurrencyResource;
use Modules\Configuration\Services\Currencies\CreateCurrencyService;
use Modules\Configuration\Services\Currencies\DeleteCurrencyService;
use Modules\Configuration\Services\Currencies\GetCurrencyService;
use Modules\Configuration\Services\Currencies\ListCurrenciesService;
use Modules\Configuration\Services\Currencies\UpdateCurrencyService;
use Modules\Core\DTOs\PagedResult;

final class CurrencyController extends Controller
{
    public function __construct(
        private readonly ListCurrenciesService $listCurrencies,
        private readonly GetCurrencyService $getCurrency,
        private readonly CreateCurrencyService $createCurrency,
        private readonly UpdateCurrencyService $updateCurrency,
        private readonly DeleteCurrencyService $deleteCurrency,
    ) {}

    public function index(ListCurrencyRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['code'])) {
            $criteria['code'] = (string) $validated['code'];
        }
        if (isset($validated['name'])) {
            $criteria['name'] = (string) $validated['name'];
        }
        if (array_key_exists('is_active', $validated)) {
            $criteria['is_active'] = (bool) $validated['is_active'];
        }

        $result = $this->listCurrencies->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => CurrencyResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $currency): JsonResponse|CurrencyResource
    {
        $result = $this->getCurrency->execute($currency);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CurrencyResource($result->valueOrFail());
    }

    public function store(UpsertCurrencyRequest $request): JsonResponse|CurrencyResource
    {
        $result = $this->createCurrency->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CurrencyResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCurrencyRequest $request, int|string $currency): JsonResponse|CurrencyResource
    {
        $result = $this->updateCurrency->execute($currency, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CONFIGURATION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CurrencyResource($result->valueOrFail());
    }

    public function destroy(int|string $currency): JsonResponse
    {
        $result = $this->deleteCurrency->execute($currency);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
