<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\UseCases\Languages\CreateLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\DeleteLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\GetLanguageServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\ListLanguagesServiceInterface;
use Modules\Configuration\Application\Contracts\UseCases\Languages\UpdateLanguageServiceInterface;
use Modules\Configuration\Presentation\Http\Requests\ListLanguageRequest;
use Modules\Configuration\Presentation\Http\Requests\UpsertLanguageRequest;
use Modules\Configuration\Presentation\Http\Resources\LanguageResource;
use Modules\Core\Application\DTO\PagedResult;

final class LanguageController extends Controller
{
    public function __construct(
        private readonly ListLanguagesServiceInterface $listLanguages,
        private readonly GetLanguageServiceInterface $getLanguage,
        private readonly CreateLanguageServiceInterface $createLanguage,
        private readonly UpdateLanguageServiceInterface $updateLanguage,
        private readonly DeleteLanguageServiceInterface $deleteLanguage,
    ) {
    }

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
            return response()->json(['message' => $result->error()?->message], 422);
        }

        $page = $result->value();
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
            return response()->json(['message' => $result->error()?->message], 404);
        }

        return new LanguageResource($result->value());
    }

    public function store(UpsertLanguageRequest $request): JsonResponse|LanguageResource
    {
        $result = $this->createLanguage->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->error()?->message], 422);
        }

        return (new LanguageResource($result->value()))->response()->setStatusCode(201);
    }

    public function update(UpsertLanguageRequest $request, int|string $language): JsonResponse|LanguageResource
    {
        $result = $this->updateLanguage->execute($language, $request->validated());

        if ($result->isFailure()) {
            $status = $result->error()?->code === 'CONFIGURATION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->error()?->message], $status);
        }

        return new LanguageResource($result->value());
    }

    public function destroy(int|string $language): JsonResponse
    {
        $result = $this->deleteLanguage->execute($language);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->error()?->message], 404);
        }

        return response()->json(null, 204);
    }
}
