<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Http\Requests\ListCountryRequest;
use Modules\Configuration\Http\Requests\UpsertCountryRequest;
use Modules\Configuration\Http\Resources\CountryResource;
use Modules\Configuration\Services\Countries\CreateCountryService;
use Modules\Configuration\Services\Countries\DeleteCountryService;
use Modules\Configuration\Services\Countries\GetCountryService;
use Modules\Configuration\Services\Countries\ListCountriesService;
use Modules\Configuration\Services\Countries\UpdateCountryService;
use Modules\Core\DTOs\PagedResult;

final class CountryController extends Controller
{
    public function __construct(
        private readonly ListCountriesService $listCountries,
        private readonly GetCountryService $getCountry,
        private readonly CreateCountryService $createCountry,
        private readonly UpdateCountryService $updateCountry,
        private readonly DeleteCountryService $deleteCountry,
    ) {}

    public function index(ListCountryRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['code'])) {
            $criteria['code'] = (string) $validated['code'];
        }
        if (isset($validated['name'])) {
            $criteria['name'] = (string) $validated['name'];
        }

        $result = $this->listCountries->execute(
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
            'data' => CountryResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $country): JsonResponse|CountryResource
    {
        $result = $this->getCountry->execute($country);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CountryResource($result->valueOrFail());
    }

    public function store(UpsertCountryRequest $request): JsonResponse|CountryResource
    {
        $result = $this->createCountry->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CountryResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCountryRequest $request, int|string $country): JsonResponse|CountryResource
    {
        $result = $this->updateCountry->execute($country, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CONFIGURATION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CountryResource($result->valueOrFail());
    }

    public function destroy(int|string $country): JsonResponse
    {
        $result = $this->deleteCountry->execute($country);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
