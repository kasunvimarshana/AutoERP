<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Repositories\OrganizationUnitTypeRepositoryInterface;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\TenantEntitlementService;
use Throwable;

final class OrganizationUnitService
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly OrganizationUnitTypeRepositoryInterface $types,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantEntitlementService $entitlements,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly OrganizationHierarchyService $hierarchy,
        private readonly OrganizationUnitContext $organizationUnitContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string|null $tenantId = null): Result
    {
        try {
            $resolvedTenantId = $this->organizationUnitContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->units->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.list');
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->units->findById($id);
            if ($record === null || ! $this->isRecordInTenantScope($record)) {
                return $this->notFound();
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.get', ['organization_unit_id' => (string) $id]);
        }
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->organizationUnitContext->resolveTenantId(
                    $this->toNullableInt($payload['tenant_id'] ?? null),
                );
                if ($this->tenants->lockById($tenantId) === null) {
                    return Result::failure(new Error(OrganizationUnitErrorCode::TENANT_NOT_FOUND, 'Tenant not found.'));
                }

                $unitLimit = $this->entitlements->limit($tenantId, 'max_organization_units');
                if ($unitLimit !== null && $this->units->countByTenant($tenantId) >= $unitLimit) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::PLAN_LIMIT_REACHED,
                        'The tenant plan organization unit limit has been reached.',
                    ));
                }

                $typeId = $this->validatedTypeId($tenantId, $payload['type_id'] ?? null);
                $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
                if ($parentId === null) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::INVALID_VALUE,
                        'Select a parent organization unit. The tenant root is provisioned by the platform workflow.',
                    ));
                }

                $unit = $this->hierarchy->createUnit($tenantId, [
                    'type_id' => $typeId,
                    'parent_id' => $parentId,
                    'name' => $this->domain->normalizeName((string) ($payload['name'] ?? '')),
                    'code' => $this->domain->normalizeOptionalText(
                        isset($payload['code']) ? (string) $payload['code'] : null,
                        100,
                    ),
                    'image_path' => $this->domain->normalizeOptionalText(
                        isset($payload['image_path']) ? (string) $payload['image_path'] : null,
                        2048,
                    ),
                    'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
                    'description' => $this->domain->normalizeOptionalText(
                        isset($payload['description']) ? (string) $payload['description'] : null,
                    ),
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                ]);

                return Result::success($this->record($unit));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.create');
        }
    }

    /** @param array<string, mixed> $payload */
    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->units->findById($id);
            if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                return $this->notFound();
            }

            $tenantId = (int) $existing->require('tenant_id');
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                return Result::failure(new Error(
                    OrganizationUnitErrorCode::INVALID_VALUE,
                    'The expected organization unit version is required.',
                ));
            }

            $typeId = array_key_exists('type_id', $payload)
                ? $this->validatedTypeId($tenantId, $payload['type_id'])
                : $existing->get('type_id');

            $unit = $this->hierarchy->updateUnit($tenantId, (int) $existing->id(), $expectedVersion, [
                'type_id' => $typeId,
                'parent_id' => array_key_exists('parent_id', $payload)
                    ? ($payload['parent_id'] === null ? null : (int) $payload['parent_id'])
                    : $existing->get('parent_id'),
                'name' => $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name'))),
                'code' => array_key_exists('code', $payload)
                    ? $this->domain->normalizeOptionalText(
                        isset($payload['code']) ? (string) $payload['code'] : null,
                        100,
                    )
                    : $existing->get('code'),
                'image_path' => array_key_exists('image_path', $payload)
                    ? $this->domain->normalizeOptionalText(
                        isset($payload['image_path']) ? (string) $payload['image_path'] : null,
                        2048,
                    )
                    : $existing->get('image_path'),
                'is_active' => array_key_exists('is_active', $payload)
                    ? (bool) $payload['is_active']
                    : (bool) $existing->get('is_active', true),
                'description' => array_key_exists('description', $payload)
                    ? $this->domain->normalizeOptionalText(
                        isset($payload['description']) ? (string) $payload['description'] : null,
                    )
                    : $existing->get('description'),
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata')),
            ]);

            return Result::success($this->record($unit));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.update', ['organization_unit_id' => (string) $id]);
        }
    }

    public function activate(int|string $id, int $expectedVersion): Result
    {
        return $this->setActivationState($id, $expectedVersion, true);
    }

    public function deactivate(int|string $id, int $expectedVersion): Result
    {
        return $this->setActivationState($id, $expectedVersion, false);
    }

    public function delete(int|string $id, int $expectedVersion): Result
    {
        try {
            $existing = $this->units->findById($id);
            if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                return $this->notFound();
            }

            $this->hierarchy->deleteUnit((int) $existing->require('tenant_id'), (int) $existing->id(), $expectedVersion);

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.delete', ['organization_unit_id' => (string) $id]);
        }
    }

    private function setActivationState(int|string $id, int $expectedVersion, bool $isActive): Result
    {
        try {
            $existing = $this->units->findById($id);
            if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                return $this->notFound();
            }

            $unit = $this->hierarchy->setActive(
                (int) $existing->require('tenant_id'),
                (int) $existing->id(),
                $expectedVersion,
                $isActive,
            );

            return Result::success($this->record($unit));
        } catch (Throwable $exception) {
            return $this->failure($exception, $isActive ? 'organization-unit.activate' : 'organization-unit.deactivate', [
                'organization_unit_id' => (string) $id,
            ]);
        }
    }

    private function validatedTypeId(int $tenantId, mixed $candidate): ?int
    {
        if ($candidate === null || $candidate === '') {
            return null;
        }

        $typeId = (int) $candidate;
        $type = $this->types->findById($typeId);
        if ($type === null || (int) $type->require('tenant_id') !== $tenantId) {
            throw new \DomainException('Organization unit type must belong to the current tenant.');
        }

        return $typeId;
    }

    private function isRecordInTenantScope(DataRecord $record): bool
    {
        return (int) $record->require('tenant_id') === $this->organizationUnitContext->requireTenantId();
    }

    private function notFound(): Result
    {
        return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit not found.'));
    }

    /** @param array<string, mixed> $context */
    private function failure(Throwable $exception, string $operation, array $context = []): Result
    {
        return Result::failure($this->errorNormalizer->normalize(
            $exception,
            OrganizationUnitErrorCode::INVALID_VALUE,
            ['operation' => $operation, ...$context],
        ));
    }

    private function record(OrganizationUnitModel $model): DataRecord
    {
        return new DataRecord($model->attributesToArray());
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
