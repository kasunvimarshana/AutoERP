<?php

declare(strict_types=1);

namespace Modules\Configuration\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\DTOs\DeleteConfigurationRecordDTO;
use Modules\Configuration\Application\DTOs\UpsertConfigurationRecordDTO;
use Modules\Configuration\Application\Orchestrators\ConfigurationRecordOrchestrator;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;
use Modules\Configuration\Domain\Exceptions\ConfigurationConflictException;
use Modules\Configuration\Domain\Exceptions\ConfigurationDeletionBlockedException;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;
use Modules\Configuration\Presentation\Requests\StoreConfigurationRecordRequest;
use Modules\Configuration\Presentation\Requests\UpdateConfigurationRecordRequest;

class ConfigurationRecordController extends Controller
{
    public function __construct(
        private readonly ConfigurationRecordOrchestrator $orchestrator,
    ) {
    }

    public function index(Request $request, string $type): JsonResponse
    {
        $recordType = $this->resolveType($type);
        $perPage = max(1, min(100, (int) $request->query('per_page', '20')));

        $paginated = $this->orchestrator->list($recordType, $perPage);

        return response()->json($paginated);
    }

    public function show(string $type, int $id): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->orchestrator->show($this->resolveType($type), $id),
            ]);
        } catch (ConfigurationRecordNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(StoreConfigurationRecordRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $type = ConfigurationRecordType::from((string) $payload['type']);

            $record = $this->orchestrator->upsert(new UpsertConfigurationRecordDTO(
                type: $type,
                payload: (array) $payload['payload'],
            ));

            return response()->json([
                'success' => true,
                'data' => $record->fresh()?->toArray() ?? $record->toArray(),
            ], 201);
        } catch (ConfigurationConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function update(UpdateConfigurationRecordRequest $request, string $type, int $id): JsonResponse
    {
        try {
            $payload = $request->validated();

            $record = $this->orchestrator->upsert(new UpsertConfigurationRecordDTO(
                type: $this->resolveType($type),
                payload: (array) $payload['payload'],
                id: $id,
                expectedRowVersion: (int) $payload['expected_row_version'],
            ));

            return response()->json([
                'success' => true,
                'data' => $record->fresh()?->toArray() ?? $record->toArray(),
            ]);
        } catch (ConfigurationRecordNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConfigurationConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        try {
            $this->orchestrator->delete(new DeleteConfigurationRecordDTO(
                type: $this->resolveType($type),
                id: $id,
            ));

            return response()->json([], 204);
        } catch (ConfigurationRecordNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConfigurationDeletionBlockedException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    private function resolveType(string $type): ConfigurationRecordType
    {
        return ConfigurationRecordType::from($type);
    }
}
