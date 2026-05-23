<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\DTOs\CountryData;
use Modules\Configuration\Application\Services\ConfigurationService;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;
use Modules\Configuration\Presentation\Http\Controllers\Concerns\HandlesConfigurationHttp;
use Modules\Configuration\Presentation\Http\Requests\StoreCountryRequest;
use Modules\Configuration\Presentation\Http\Requests\UpdateCountryRequest;
use Modules\Configuration\Presentation\Http\Resources\CountryResource;

class CountryController extends Controller
{
    use HandlesConfigurationHttp;

    public function __construct(private readonly ConfigurationService $configuration) {}

    public function index(Request $request): mixed
    {
        $records = $this->configuration->listCountries(
            filters: $this->filters($request, ['code', 'name']),
            perPage: $this->perPage($request),
        );

        return CountryResource::collection($records);
    }

    public function store(StoreCountryRequest $request): JsonResponse
    {
        $country = $this->configuration->createCountry(CountryData::fromArray($request->validated()));

        return (new CountryResource($country))->response()->setStatusCode(201);
    }

    public function show(int|string $country): CountryResource|JsonResponse
    {
        try {
            return new CountryResource($this->configuration->findCountry($country));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateCountryRequest $request, int|string $country): CountryResource|JsonResponse
    {
        try {
            return new CountryResource($this->configuration->updateCountry($country, CountryData::fromArray($request->validated())));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $country): JsonResponse
    {
        try {
            $this->configuration->deleteCountry($country);

            return response()->json(null, 204);
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
