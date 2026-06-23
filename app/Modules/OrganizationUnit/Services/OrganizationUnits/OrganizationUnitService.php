<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
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
        private readonly OrganizationUnitContext $organizationUnitContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            $resolvedTenantId = $this->organizationUnitContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->units->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.list'],
            ));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $resolvedTenantId = $this->organizationUnitContext->requireTenantId();
            $record = $this->units->findById($id);
            if ($record === null || (int) $record->require('tenant_id') !== $resolvedTenantId) {
                return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.get', 'organization_unit_id' => (string) $id],
            ));
        }
    }

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

                $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
                if ($this->units->findByTenantAndName($tenantId, $name) !== null) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::CONFLICT,
                        'Organization unit name already exists for tenant.',
                    ));
                }

                $typeId = isset($payload['type_id']) ? (int) $payload['type_id'] : null;
                if ($typeId !== null) {
                    $type = $this->types->findById($typeId);
                    if ($type === null || (int) $type->require('tenant_id') !== $tenantId) {
                        return Result::failure(new Error(
                            OrganizationUnitErrorCode::TENANT_MISMATCH,
                            'Type must belong to same tenant.',
                        ));
                    }
                }

                $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
                if ($parentId !== null) {
                    $parent = $this->units->findById($parentId);
                    if ($parent === null || (int) $parent->require('tenant_id') !== $tenantId) {
                        return Result::failure(new Error(
                            OrganizationUnitErrorCode::TENANT_MISMATCH,
                            'Parent unit must belong to same tenant.',
                        ));
                    }
                }

                $record = $this->units->create([
                    'tenant_id' => $tenantId,
                    'type_id' => $typeId,
                    'parent_id' => $parentId,
                    'name' => $name,
                    'code' => $this->domain->normalizeOptionalText(
                        isset($payload['code']) ? (string) $payload['code'] : null,
                        255,
                    ),
                    'image_path' => $this->domain->normalizeOptionalText(
                        isset($payload['image_path']) ? (string) $payload['image_path'] : null,
                        2048,
                    ),
                    'path' => $this->domain->normalizeOptionalText(
                        isset($payload['path']) ? (string) $payload['path'] : null,
                        1024,
                    ),
                    'depth' => $this->domain->normalizeDepth((int) ($payload['depth'] ?? 0)),
                    'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
                    'description' => $this->domain->normalizeOptionalText(
                        isset($payload['description']) ? (string) $payload['description'] : null,
                    ),
                    '_lft' => isset($payload['_lft']) ? (int) $payload['_lft'] : 0,
                    '_rgt' => isset($payload['_rgt']) ? (int) $payload['_rgt'] : 0,
                    'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.create'],
            ));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->units->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::NOT_FOUND,
                        'Organization unit not found.',
                    ));
                }

                $tenantId = (int) $existing->require('tenant_id');
                if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::TENANT_MISMATCH,
                        'Organization unit tenant cannot be changed.',
                    ));
                }

                $name = $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name')));
                $byName = $this->units->findByTenantAndName($tenantId, $name);
                if ($byName !== null && (string) $byName->id() !== (string) $existing->id()) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::CONFLICT,
                        'Organization unit name already exists for tenant.',
                    ));
                }

                $typeId = array_key_exists('type_id', $payload)
                    ? (isset($payload['type_id']) ? (int) $payload['type_id'] : null)
                    : ($existing->get('type_id') !== null ? (int) $existing->get('type_id') : null);
                if ($typeId !== null) {
                    $type = $this->types->findById($typeId);
                    if ($type === null || (int) $type->require('tenant_id') !== $tenantId) {
                        return Result::failure(new Error(
                            OrganizationUnitErrorCode::TENANT_MISMATCH,
                            'Type must belong to same tenant.',
                        ));
                    }
                }

                $parentId = array_key_exists('parent_id', $payload)
                    ? (isset($payload['parent_id']) ? (int) $payload['parent_id'] : null)
                    : ($existing->get('parent_id') !== null ? (int) $existing->get('parent_id') : null);
                if ($parentId !== null) {
                    if ((string) $parentId === (string) $id) {
                        return Result::failure(new Error(
                            OrganizationUnitErrorCode::CONFLICT,
                            'Organization unit cannot be its own parent.',
                        ));
                    }

                    $parent = $this->units->findById($parentId);
                    if ($parent === null || (int) $parent->require('tenant_id') !== $tenantId) {
                        return Result::failure(new Error(
                            OrganizationUnitErrorCode::TENANT_MISMATCH,
                            'Parent unit must belong to same tenant.',
                        ));
                    }
                }

                $record = $this->units->update($id, [
                    'type_id' => $typeId,
                    'parent_id' => $parentId,
                    'name' => $name,
                    'code' => array_key_exists('code', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['code']) ? (string) $payload['code'] : null,
                            255,
                        )
                        : $existing->get('code'),
                    'image_path' => array_key_exists('image_path', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['image_path']) ? (string) $payload['image_path'] : null,
                            2048,
                        )
                        : $existing->get('image_path'),
                    'path' => array_key_exists('path', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['path']) ? (string) $payload['path'] : null,
                            1024,
                        )
                        : $existing->get('path'),
                    'depth' => array_key_exists('depth', $payload)
                        ? $this->domain->normalizeDepth((int) $payload['depth'])
                        : (int) $existing->get('depth', 0),
                    'is_active' => array_key_exists('is_active', $payload)
                        ? (bool) $payload['is_active']
                        : (bool) $existing->get('is_active', true),
                    'description' => array_key_exists('description', $payload)
                        ? $this->domain->normalizeOptionalText(
                            isset($payload['description']) ? (string) $payload['description'] : null,
                        )
                        : $existing->get('description'),
                    '_lft' => array_key_exists('_lft', $payload)
                        ? (int) $payload['_lft']
                        : (int) $existing->get('_lft', 0),
                    '_rgt' => array_key_exists('_rgt', $payload)
                        ? (int) $payload['_rgt']
                        : (int) $existing->get('_rgt', 0),
                    'metadata' => array_key_exists('metadata', $payload)
                        ? $this->domain->normalizeMetadata($payload['metadata'])
                        : $this->domain->normalizeMetadata($existing->get('metadata')),
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.update', 'organization_unit_id' => (string) $id],
            ));
        }
    }

    public function activate(int|string $id): Result
    {
        return $this->setActivationState($id, true);
    }

    public function deactivate(int|string $id): Result
    {
        return $this->setActivationState($id, false);
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->units->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::NOT_FOUND,
                        'Organization unit not found.',
                    ));
                }

                $tenantId = (int) $existing->require('tenant_id');
                $deleted = $this->units->delete($id);

                if ($deleted) {
                }

                return Result::success($deleted);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                ['operation' => 'organization-unit.delete', 'organization_unit_id' => (string) $id],
            ));
        }
    }

    private function setActivationState(int|string $id, bool $isActive): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $isActive): Result {
                $existing = $this->units->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::NOT_FOUND,
                        'Organization unit not found.',
                    ));
                }

                $record = $this->units->update($id, [
                    'is_active' => $isActive,
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]);

                return Result::success($record);
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                OrganizationUnitErrorCode::INVALID_VALUE,
                [
                    'operation' => $isActive ? 'organization-unit.activate' : 'organization-unit.deactivate',
                    'organization_unit_id' => (string) $id,
                ],
            ));
        }
    }

    private function isRecordInTenantScope(DataRecord $record): bool
    {
        return (int) $record->require('tenant_id') === $this->organizationUnitContext->requireTenantId();
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
