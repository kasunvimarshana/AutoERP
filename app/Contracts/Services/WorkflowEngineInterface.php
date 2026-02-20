<?php

namespace App\Contracts\Services;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkflowEngineInterface
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function createDefinition(array $data): WorkflowDefinition;

    public function updateDefinition(string $id, array $data): WorkflowDefinition;

    public function startInstance(string $definitionId, string $entityType, string $entityId, string $tenantId, ?string $userId = null, array $context = []): WorkflowInstance;

    public function transition(string $instanceId, string $transitionId, string $tenantId, ?string $userId = null, ?string $comment = null): WorkflowInstance;

    public function cancelInstance(string $instanceId, string $tenantId, ?string $userId = null): WorkflowInstance;

    public function getInstance(string $entityType, string $entityId, string $tenantId): ?WorkflowInstance;
}
