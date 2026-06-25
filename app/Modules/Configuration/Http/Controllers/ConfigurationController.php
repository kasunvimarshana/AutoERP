<?php

declare(strict_types=1);

namespace Modules\Configuration\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LogicException;
use Modules\Configuration\Contracts\ConfigurationDefinitionRegistryInterface;
use Modules\Configuration\Http\Requests\CreateConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\DeleteConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\ListConfigurationEntriesRequest;
use Modules\Configuration\Http\Requests\ListConfigurationHistoryRequest;
use Modules\Configuration\Http\Requests\RollbackConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\UpdateConfigurationEntryRequest;
use Modules\Configuration\Http\Requests\ViewConfigurationRequest;
use Modules\Configuration\Http\Resources\ConfigurationDefinitionResource;
use Modules\Configuration\Http\Resources\ConfigurationEntryResource;
use Modules\Configuration\Http\Resources\ConfigurationRevisionResource;
use Modules\Configuration\Http\Resources\ResolvedConfigurationResource;
use Modules\Configuration\Services\ConfigurationEntryService;

final class ConfigurationController extends Controller
{
    public function __construct(
        private readonly ConfigurationDefinitionRegistryInterface $definitions,
        private readonly ConfigurationEntryService $entries,
    ) {}

    public function definitions(ViewConfigurationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => ConfigurationDefinitionResource::collection($this->definitions->all())->resolve($request),
        ]);
    }

    public function index(ListConfigurationEntriesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->entries->list(
            $this->scope($request),
            isset($validated['search']) ? (string) $validated['search'] : null,
            isset($validated['owner']) ? (string) $validated['owner'] : null,
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => ConfigurationEntryResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'from' => $page->firstItem(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
            ],
            'existing_keys' => $this->entries->existingKeys($this->scope($request)),
        ]);
    }

    public function show(ViewConfigurationRequest $request, string $key): ConfigurationEntryResource
    {
        return new ConfigurationEntryResource($this->entries->exact($this->scope($request), $key));
    }

    public function resolved(ViewConfigurationRequest $request, string $key): ResolvedConfigurationResource
    {
        return new ResolvedConfigurationResource($this->entries->resolvedCurrent($key));
    }

    public function store(CreateConfigurationEntryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $resource = new ConfigurationEntryResource($this->entries->create(
            $this->scope($request),
            (string) $validated['key'],
            $validated['value'],
        ));

        return $resource->response()->setStatusCode(201);
    }

    public function update(UpdateConfigurationEntryRequest $request, string $key): ConfigurationEntryResource
    {
        $validated = $request->validated();

        return new ConfigurationEntryResource($this->entries->update(
            $this->scope($request),
            $key,
            (int) $validated['expected_version'],
            $validated['value'],
        ));
    }

    public function history(ListConfigurationHistoryRequest $request, string $key): JsonResponse
    {
        $page = $this->entries->history(
            $this->scope($request),
            $key,
            $request->page(),
            $request->perPage(),
        );

        return response()->json([
            'data' => ConfigurationRevisionResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function rollback(RollbackConfigurationEntryRequest $request, string $key): JsonResponse|ConfigurationEntryResource
    {
        $validated = $request->validated();
        $entry = $this->entries->rollback(
            $this->scope($request),
            $key,
            (int) $validated['revision_id'],
            (int) $validated['expected_version'],
            (string) $validated['reason'],
        );

        return $entry === null
            ? response()->json(['data' => null])
            : new ConfigurationEntryResource($entry);
    }

    public function destroy(DeleteConfigurationEntryRequest $request, string $key): JsonResponse
    {
        $validated = $request->validated();
        $this->entries->delete($this->scope($request), $key, (int) $validated['expected_version']);

        return response()->json(null, 204);
    }

    private function scope(Request $request): string
    {
        $scope = $request->route('scope');

        return is_string($scope)
            ? $scope
            : throw new LogicException('Configuration route scope is missing.');
    }
}
