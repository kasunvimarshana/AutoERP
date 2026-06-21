<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\ReferenceData\Http\Requests\CreateLanguageRequest;
use Modules\ReferenceData\Http\Requests\ListReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\LookupReferenceDataRequest;
use Modules\ReferenceData\Http\Requests\SetReferenceStatusRequest;
use Modules\ReferenceData\Http\Requests\UpdateLanguageRequest;
use Modules\ReferenceData\Http\Resources\LanguageResource;
use Modules\ReferenceData\Services\LanguageCatalogService;

final class LanguageController extends Controller
{
    use BuildsReferenceResponses;

    public function __construct(private readonly LanguageCatalogService $catalog) {}

    public function index(ListReferenceDataRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->catalog->list(
            isset($validated['search']) ? (string) $validated['search'] : null,
            array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            $request->page(),
            $request->perPage(),
        );

        return $this->pageResponse($page, LanguageResource::class);
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

        return $this->pageResponse($page, LanguageResource::class);
    }

    public function show(ListReferenceDataRequest $request, int $language): LanguageResource
    {
        return new LanguageResource($this->catalog->find($language));
    }

    public function store(CreateLanguageRequest $request): JsonResponse
    {
        return (new LanguageResource($this->catalog->create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateLanguageRequest $request, int $language): LanguageResource
    {
        $validated = $request->validated();
        return new LanguageResource($this->catalog->update($language, (int) $validated['expected_version'], $validated));
    }

    public function setStatus(SetReferenceStatusRequest $request, int $language): LanguageResource
    {
        $validated = $request->validated();
        return new LanguageResource($this->catalog->setActive(
            $language,
            (int) $validated['expected_version'],
            (bool) $validated['is_active'],
        ));
    }
}
