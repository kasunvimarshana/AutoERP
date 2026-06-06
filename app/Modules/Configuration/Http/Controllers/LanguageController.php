<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Http\Requests\ListLanguageRequest;
use Modules\Configuration\Http\Requests\UpsertLanguageRequest;
use Modules\Configuration\Http\Resources\LanguageResource;
use Modules\Configuration\Services\Languages\CreateLanguageService;
use Modules\Configuration\Services\Languages\DeleteLanguageService;
use Modules\Configuration\Services\Languages\GetLanguageService;
use Modules\Configuration\Services\Languages\ListLanguagesService;
use Modules\Configuration\Services\Languages\UpdateLanguageService;
use Modules\Core\DTOs\PagedResult;

final class LanguageController extends Controller
{
    public function __construct(
        private readonly ListLanguagesService $listLanguages,
        private readonly GetLanguageService $getLanguage,
        private readonly CreateLanguageService $createLanguage,
        private readonly UpdateLanguageService $updateLanguage,
        private readonly DeleteLanguageService $deleteLanguage,
    ) {}

    public function index(ListLanguageRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['code'])) {
            $criteria['code'] = (string) $validated['code'];
        }
        if (isset($validated['name'])) {
            $criteria['name'] = (string) $validated['name'];
        }

        $result = $this->listLanguages->execute(
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
            'data' => LanguageResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $language): JsonResponse|LanguageResource
    {
        $result = $this->getLanguage->execute($language);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new LanguageResource($result->valueOrFail());
    }

    public function store(UpsertLanguageRequest $request): JsonResponse|LanguageResource
    {
        $result = $this->createLanguage->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new LanguageResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertLanguageRequest $request, int|string $language): JsonResponse|LanguageResource
    {
        $result = $this->updateLanguage->execute($language, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'CONFIGURATION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new LanguageResource($result->valueOrFail());
    }

    public function destroy(int|string $language): JsonResponse
    {
        $result = $this->deleteLanguage->execute($language);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
