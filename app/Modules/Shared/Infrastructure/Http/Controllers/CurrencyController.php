<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Contracts\FindCurrenciesServiceInterface;
use Modules\Shared\Infrastructure\Http\Resources\CurrencyResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly FindCurrenciesServiceInterface $findCurrenciesService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['code', 'name', 'is_active']);
        $perPage = (int) $request->query('per_page', '15');
        $page = (int) $request->query('page', '1');
        $sort = $request->query('sort');

        $currencies = $this->findCurrenciesService->list($filters, $perPage, $page, $sort);

        return CurrencyResource::collection($currencies)->response();
    }

    public function show(int $id): JsonResponse
    {
        $currency = $this->findCurrenciesService->find($id);

        if ($currency === null) {
            throw new NotFoundHttpException('Currency not found.');
        }

        return (new CurrencyResource($currency))->response();
    }
}
