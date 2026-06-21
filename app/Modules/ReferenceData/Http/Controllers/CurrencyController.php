<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReferenceData\Http\Requests\CreateCurrencyRequest;
use Modules\ReferenceData\Http\Requests\ListReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\LookupReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\SetReferenceStatusRequest;
use Modules\ReferenceData\Http\Requests\UpdateCurrencyRequest;
use Modules\ReferenceData\Http\Resources\CurrencyResource;
use Modules\ReferenceData\Services\CurrencyCatalogService;

final class CurrencyController extends Controller
{
    use BuildsReferenceResponses;

    public function __construct(private readonly CurrencyCatalogService $catalog) {}

    public function index(ListReferenceDataRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->catalog->list(
            isset($validated['search']) ? (string) $validated['search'] : null,
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            $request->page(),
            $request->perPage(),
        );

        return $this->pageResponse($page, CurrencyResource::class);
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

        return $this->pageResponse($page, CurrencyResource::class);
    }

    public function show(ListReferenceDataRequest $request, int $currency): CurrencyResource
    {
        return new CurrencyResource($this->catalog->find($currency));
    }

    public function store(CreateCurrencyRequest $request): JsonResponse
    {
        return (new CurrencyResource($this->catalog->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateCurrencyRequest $request, int $currency): CurrencyResource
    {
        $validated = $request->validated();
        return new CurrencyResource($this->catalog->update($currency, (int) $validated['expected_version'], $validated));
    }

    public function setStatus(SetReferenceStatusRequest $request, int $currency): CurrencyResource
    {
        $validated = $request->validated();
        return new CurrencyResource($this->catalog->setActive(
            $currency,
            (int) $validated['expected_version'],
            (bool) $validated['is_active'],
        ));
    }
}
