<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Supplier\Application\Repositories\SupplierCategoryRepositoryInterface;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierCategoryRequest;

final class SupplierCategoryController extends Controller
{
    public function __construct(
        private readonly SupplierCategoryRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function index(): JsonResponse
    {
        $records = $this->repository->list([
            'tenant_id' => $this->tenantId(),
        ]);

        return response()->json(['data' => array_map(static fn ($item): array => $item->toArray(), $records)]);
    }

    public function store(UpsertSupplierCategoryRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $record = $this->repository->create([
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->currentOrganizationUnit->currentOrganizationUnitId(),
            'metadata' => $payload['metadata'] ?? null,
            'code' => strtoupper(trim((string) $payload['code'])),
            'name' => trim((string) $payload['name']),
            'description' => $payload['description'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_by' => $this->currentUser->currentUserId(),
            'updated_by' => $this->currentUser->currentUserId(),
            'row_version' => 1,
        ]);

        return response()->json(['data' => $record->toArray()], 201);
    }

    public function update(UpsertSupplierCategoryRequest $request, int|string $id): JsonResponse
    {
        $existing = $this->repository->findById($id);
        if ($existing === null || (int) $existing->require('tenant_id') !== $this->tenantId()) {
            return response()->json(['message' => 'Supplier category not found.'], 404);
        }

        $payload = $request->validated();
        $record = $this->repository->update($id, [
            'metadata' => $payload['metadata'] ?? $existing->get('metadata'),
            'code' => isset($payload['code']) ? strtoupper(trim((string) $payload['code'])) : $existing->get('code'),
            'name' => isset($payload['name']) ? trim((string) $payload['name']) : $existing->get('name'),
            'description' => $payload['description'] ?? $existing->get('description'),
            'is_active' => array_key_exists('is_active', $payload)
                ? (bool) $payload['is_active']
                : (bool) $existing->get('is_active'),
            'updated_by' => $this->currentUser->currentUserId(),
            'row_version' => ((int) $existing->get('row_version', 1)) + 1,
        ]);

        return response()->json(['data' => $record->toArray()]);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $existing = $this->repository->findById($id);
        if ($existing === null || (int) $existing->require('tenant_id') !== $this->tenantId()) {
            return response()->json(['message' => 'Supplier category not found.'], 404);
        }

        $this->repository->delete($id);

        return response()->json(null, 204);
    }

    private function tenantId(): int
    {
        return (int) $this->currentTenant->currentTenantId();
    }
}
