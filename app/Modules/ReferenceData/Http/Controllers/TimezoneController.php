<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReferenceData\Http\Requests\CreateTimezoneRequest;
use Modules\ReferenceData\Http\Requests\ListReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\LookupReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\SetReferenceStatusRequest;
use Modules\ReferenceData\Http\Requests\UpdateTimezoneRequest;
use Modules\ReferenceData\Http\Resources\TimezoneResource;
use Modules\ReferenceData\Services\TimezoneCatalogService;

final class TimezoneController extends Controller
{
    use BuildsReferenceResponses;

    public function __construct(private readonly TimezoneCatalogService $catalog) {}

    public function index(ListReferenceDataRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->catalog->list(
            isset($validated['search']) ? (string) $validated['search'] : null,
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            $request->page(),
            $request->perPage(),
        );

        return $this->pageResponse($page, TimezoneResource::class);
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

        return $this->pageResponse($page, TimezoneResource::class);
    }

    public function show(ListReferenceDataRequest $request, int $timezone): TimezoneResource
    {
        return new TimezoneResource($this->catalog->find($timezone));
    }

    public function store(CreateTimezoneRequest $request): JsonResponse
    {
        return (new TimezoneResource($this->catalog->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateTimezoneRequest $request, int $timezone): TimezoneResource
    {
        $validated = $request->validated();
        return new TimezoneResource($this->catalog->update($timezone, (int) $validated['expected_version'], $validated));
    }

    public function setStatus(SetReferenceStatusRequest $request, int $timezone): TimezoneResource
    {
        $validated = $request->validated();
        return new TimezoneResource($this->catalog->setActive(
            $timezone,
            (int) $validated['expected_version'],
            (bool) $validated['is_active'],
        ));
    }
}
