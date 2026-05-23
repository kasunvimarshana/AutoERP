<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\DTOs\LanguageData;
use Modules\Configuration\Application\Services\ConfigurationService;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;
use Modules\Configuration\Presentation\Http\Controllers\Concerns\HandlesConfigurationHttp;
use Modules\Configuration\Presentation\Http\Requests\StoreLanguageRequest;
use Modules\Configuration\Presentation\Http\Requests\UpdateLanguageRequest;
use Modules\Configuration\Presentation\Http\Resources\LanguageResource;

class LanguageController extends Controller
{
    use HandlesConfigurationHttp;

    public function __construct(private readonly ConfigurationService $configuration) {}

    public function index(Request $request): mixed
    {
        $records = $this->configuration->listLanguages(
            filters: $this->filters($request, ['code', 'name']),
            perPage: $this->perPage($request),
        );

        return LanguageResource::collection($records);
    }

    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $language = $this->configuration->createLanguage(LanguageData::fromArray($request->validated()));

        return (new LanguageResource($language))->response()->setStatusCode(201);
    }

    public function show(int|string $language): LanguageResource|JsonResponse
    {
        try {
            return new LanguageResource($this->configuration->findLanguage($language));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateLanguageRequest $request, int|string $language): LanguageResource|JsonResponse
    {
        try {
            return new LanguageResource($this->configuration->updateLanguage($language, LanguageData::fromArray($request->validated())));
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $language): JsonResponse
    {
        try {
            $this->configuration->deleteLanguage($language);

            return response()->json(null, 204);
        } catch (ConfigurationRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
