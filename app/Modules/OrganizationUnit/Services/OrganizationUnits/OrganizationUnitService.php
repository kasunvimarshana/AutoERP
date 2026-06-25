<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use InvalidArgumentException;
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
use Throwable;

final class OrganizationUnitService
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly OrganizationUnitTypeRepositoryInterface $types,
        private readonly TenantRepositoryInterface $tenants,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly OrganizationUnitContext $organizationUnitContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string|null $tenantId = null): Result
    {
        try {
            $resolvedTenantId = $this->organizationUnitContext->resolveTenantId(
                $this->toNullableInt($tenantId),
            );

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
                return Result::failure(new Error(
                    OrganizationUnitErrorCode::NOT_FOUND,
                    'Organization unit not found.',
                ));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.get', $id);
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

                return Result::success($this->createForTenant($tenantId, $payload));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.create');
        }
    }

    /**
     * Creates the single root unit during tenant onboarding.
     *
     * @param array<string, mixed> $payload
     */
    public function provisionRootForTenant(int $tenantId, array $payload): DataRecord
    {
        return $this->createForTenant($tenantId, [
            ...$payload,
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $payload */
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

                return Result::success($this->updateForTenant($existing, $payload));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.update', $id);
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
                if ($this->units->hasChildren($id, $tenantId)) {
                    return Result::failure(new Error(
                        OrganizationUnitErrorCode::CONFLICT,
                        'Move or remove child organization units before deleting this unit.',
                    ));
                }

                return Result::success($this->units->delete($id));
            });
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.delete', $id);
        }
    }

    /** @param array<string, mixed> $payload */
    private function createForTenant(int $tenantId, array $payload): DataRecord
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw new InvalidArgumentException('Tenant not found.');
        }

        $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
        $this->ensureUniqueName($tenantId, $name);
        $code = $this->normalizeCode($payload['code'] ?? null);
        $this->ensureUniqueCode($tenantId, $code);
        $typeId = $this->validatedTypeId($tenantId, $payload['type_id'] ?? null);
        $parent = $this->validatedParent($tenantId, $payload['parent_id'] ?? null);

        if ($parent === null && $this->units->findRootByTenant($tenantId) !== null) {
            throw new InvalidArgumentException('A root organization unit already exists for this tenant.');
        }

        $record = $this->units->create([
            'tenant_id' => $tenantId,
            'type_id' => $typeId,
            'parent_id' => $parent?->id(),
            'name' => $name,
            'code' => $code,
            'image_path' => $this->domain->normalizeOptionalText(
                isset($payload['image_path']) ? (string) $payload['image_path'] : null,
                2048,
            ),
            'path' => null,
            'depth' => 0,
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
            'description' => $this->domain->normalizeOptionalText(
                isset($payload['description']) ? (string) $payload['description'] : null,
            ),
            'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
            'row_version' => 1,
        ]);

        [$path, $depth] = $this->hierarchyFor($record->id(), $parent);

        return $this->units->update($record->id(), [
            'path' => $path,
            'depth' => $depth,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function updateForTenant(DataRecord $existing, array $payload): DataRecord
    {
        $id = $existing->id();
        $tenantId = (int) $existing->require('tenant_id');
        if (array_key_exists('tenant_id', $payload) && (int) $payload['tenant_id'] !== $tenantId) {
            throw new InvalidArgumentException('Organization unit tenant cannot be changed.');
        }

        $name = $this->domain->normalizeName((string) ($payload['name'] ?? $existing->require('name')));
        $this->ensureUniqueName($tenantId, $name, $id);
        $code = array_key_exists('code', $payload)
            ? $this->normalizeCode($payload['code'])
            : $this->normalizeCode($existing->get('code'));
        $this->ensureUniqueCode($tenantId, $code, $id);
        $typeId = array_key_exists('type_id', $payload)
            ? $this->validatedTypeId($tenantId, $payload['type_id'])
            : $this->toNullableInt($existing->get('type_id'));
        $parentId = array_key_exists('parent_id', $payload)
            ? $payload['parent_id']
            : $existing->get('parent_id');
        $parent = $this->validatedParent($tenantId, $parentId);

        if ($parent !== null && (string) $parent->id() === (string) $id) {
            throw new InvalidArgumentException('Organization unit cannot be its own parent.');
        }
        if ($parent === null) {
            $root = $this->units->findRootByTenant($tenantId);
            if ($root !== null && (string) $root->id() !== (string) $id) {
                throw new InvalidArgumentException('A root organization unit already exists for this tenant.');
            }
        }

        $oldPath = trim((string) $existing->get('path', ''));
        if ($oldPath === '') {
            throw new InvalidArgumentException('Organization unit hierarchy is incomplete.');
        }

        [$newPath, $newDepth] = $this->hierarchyFor($id, $parent);
        if ($parent !== null && str_contains((string) $parent->get('path', ''), '/'.$id.'/')) {
            throw new InvalidArgumentException('Organization unit cannot be moved below one of its descendants.');
        }

        $this->units->update($id, [
            'type_id' => $typeId,
            'parent_id' => $parent?->id(),
            'name' => $name,
            'code' => $code,
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
            'row_version' => ((int) $existing->get('row_version', 1)) + 1,
        ]);

        return $this->units->moveHierarchy(
            $id,
            $tenantId,
            $oldPath,
            $newPath,
            $newDepth,
        );
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

                return Result::success($this->units->update($id, [
                    'is_active' => $isActive,
                    'row_version' => ((int) $existing->get('row_version', 1)) + 1,
                ]));
            });
        } catch (Throwable $exception) {
            return $this->failure(
                $exception,
                $isActive ? 'organization-unit.activate' : 'organization-unit.deactivate',
                $id,
            );
        }
    }

    private function validatedTypeId(int $tenantId, mixed $value): ?int
    {
        $typeId = $this->toNullableInt($value);
        if ($typeId === null) {
            return null;
        }

        $type = $this->types->findById($typeId);
        if ($type === null || (int) $type->require('tenant_id') !== $tenantId) {
            throw new InvalidArgumentException('Type must belong to the same tenant.');
        }

        return $typeId;
    }

    private function validatedParent(int $tenantId, mixed $value): ?DataRecord
    {
        $parentId = $this->toNullableInt($value);
        if ($parentId === null) {
            return null;
        }

        $parent = $this->units->findById($parentId);
        if ($parent === null || (int) $parent->require('tenant_id') !== $tenantId) {
            throw new InvalidArgumentException('Parent unit must belong to the same tenant.');
        }
        if (trim((string) $parent->get('path', '')) === '') {
            throw new InvalidArgumentException('Parent organization unit hierarchy is incomplete.');
        }

        return $parent;
    }

    private function ensureUniqueName(int $tenantId, string $name, int|string|null $exceptId = null): void
    {
        $existing = $this->units->findByTenantAndName($tenantId, $name);
        if ($existing !== null && (string) $existing->id() !== (string) $exceptId) {
            throw new InvalidArgumentException('Organization unit name already exists for this tenant.');
        }
    }

    private function ensureUniqueCode(int $tenantId, ?string $code, int|string|null $exceptId = null): void
    {
        if ($code === null) {
            return;
        }

        $existing = $this->units->findByTenantAndCode($tenantId, $code);
        if ($existing !== null && (string) $existing->id() !== (string) $exceptId) {
            throw new InvalidArgumentException('Organization unit code already exists for this tenant.');
        }
    }

    private function normalizeCode(mixed $value): ?string
    {
        $code = $this->domain->normalizeOptionalText(
            is_scalar($value) ? (string) $value : null,
            50,
        );

        if ($code === null) {
            return null;
        }

        $code = strtoupper($code);
        if (preg_match('/^[A-Z0-9_-]+$/', $code) !== 1) {
            throw new InvalidArgumentException('Organization unit code may contain only letters, numbers, underscores, and hyphens.');
        }

        return $code;
    }

    /** @return array{0:string,1:int} */
    private function hierarchyFor(int|string $id, ?DataRecord $parent): array
    {
        if ($parent === null) {
            return ['/'.$id.'/', 0];
        }

        return [
            rtrim((string) $parent->require('path'), '/').'/'.$id.'/',
            (int) $parent->get('depth', 0) + 1,
        ];
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

    private function failure(
        Throwable $exception,
        string $operation,
        int|string|null $id = null,
    ): Result {
        $context = ['operation' => $operation];
        if ($id !== null) {
            $context['organization_unit_id'] = (string) $id;
        }

        return Result::failure($this->errorNormalizer->normalize(
            $exception,
            OrganizationUnitErrorCode::INVALID_VALUE,
            $context,
        ));
    }
}
