<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\DTOs\CurrencyData;
use Modules\Configuration\Application\Services\ConfigurationService;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;
use Modules\Configuration\Presentation\Http\Controllers\Concerns\HandlesConfigurationHttp;
use Modules\Configuration\Presentation\Http\Requests\StoreCurrencyRequest;
use Modules\Configuration\Presentation\Http\Requests\UpdateCurrencyRequest;
use Modules\Configuration\Presentation\Http\Resources\CurrencyResource;

class CurrencyController extends Controller
{
    use HandlesConfigurationHttp;

    public function __construct(private readonly ConfigurationService $configuration) {}

    public function index(Request $request): mixed
    {
        $records = $this->configuration->listCurrencies(
            filters: $this->filters($request, ['code', 'name', 'is_active']),
            perPage: $this->perPage($request),
        );

        return CurrencyResource::collection($records);
    }

    public function store(StoreCurrencyRequest $request): JsonResponse
    {
        $currency = $this->configuration->createCurrency(CurrencyData::fromArray($request->validated()));

        return (new CurrencyResource($currency))->response()->setStatusCode(201);
    }

    public function show(int|string $currency): CurrencyResource|JsonResponse
    {
        try {
            return new CurrencyResource($this->configuration->findCurrency($currency));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateCurrencyRequest $request, int|string $currency): CurrencyResource|JsonResponse
    {
        try {
            return new CurrencyResource($this->configuration->updateCurrency($currency, CurrencyData::fromArray($request->validated())));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $currency): JsonResponse
    {
        try {
            $this->configuration->deleteCurrency($currency);

            return response()->json(null, 204);
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    /**
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    protected function filters(Request $request, array $allowed): array
    {
        $filters = collect($request->only($allowed))->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();

        if (array_key_exists('is_active', $filters)) {
            $filters['is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return array_filter($filters, fn (mixed $value): bool => $value !== null);
    }
}
