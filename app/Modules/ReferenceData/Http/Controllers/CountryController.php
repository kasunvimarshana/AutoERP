<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReferenceData\Http\Requests\CreateCountryRequest;
use Modules\ReferenceData\Http\Requests\ListReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\LookupReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\SetReferenceStatusRequest;
use Modules\ReferenceData\Http\Requests\UpdateCountryRequest;
use Modules\ReferenceData\Http\Resources\CountryResource;
use Modules\ReferenceData\Services\CountryCatalogService;

final class CountryController extends Controller
{
    use BuildsReferenceResponses;

    public function __construct(private readonly CountryCatalogService $catalog) {}

    public function index(ListReferenceDataRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->catalog->list(
            isset($validated['search']) ? (string) $validated['search'] : null,
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            $request->page(),
            $request->perPage(),
        );

        return $this->pageResponse($page, CountryResource::class);
    }

    public function lookup(LookupReferenceDataRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->catalog->list(
            isset($validated['search']) ? (string) $validated['search'] : null,
            true,
            $request->page(),
            $request->perPage(),
        );

        return $this->pageResponse($page, CountryResource::class);
    }

    public function show(ListReferenceDataRequest $request, int $country): CountryResource
    {
        return new CountryResource($this->catalog->find($country));
    }

    public function store(CreateCountryRequest $request): JsonResponse
    {
        return (new CountryResource($this->catalog->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateCountryRequest $request, int $country): CountryResource
    {
        $validated = $request->validated();
        return new CountryResource($this->catalog->update($country, (int) $validated['expected_version'], $validated));
    }

    public function setStatus(SetReferenceStatusRequest $request, int $country): CountryResource
    {
        $validated = $request->validated();
        return new CountryResource($this->catalog->setActive(
            $country,
            (int) $validated['expected_version'],
            (bool) $validated['is_active'],
        ));
    }
}
