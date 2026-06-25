<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnits;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Exceptions\OrganizationUnitException;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\Audit\OrganizationUnitAuditService;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Services\Storage\OrganizationUnitAssetStorageService;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\TenantEntitlementService;
use Throwable;

final class OrganizationUnitService
{
    public function __construct(
        private readonly OrganizationUnitRepositoryInterface $units,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantEntitlementService $entitlements,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly OrganizationHierarchyService $hierarchy,
        private readonly OrganizationUnitContext $context,
        private readonly OrganizationUnitAssetStorageService $assets,
        private readonly OrganizationUnitAuditService $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $filters */
    public function page(array $filters, int $perPage, int $page): Result
    {
        try {
            return Result::success($this->units->pageByTenant(
                $this->context->requireTenantId(),
                $filters,
                $perPage,
                $page,
            ));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.list');
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->units->findById($id);
            return $record !== null && (int) $record->get('tenant_id') === $this->context->requireTenantId()
                ? Result::success($record)
                : $this->notFound();
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.get', ['organization_unit_id' => (string) $id]);
        }
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            $unit = DB::transaction(function () use ($tenantId, $payload): OrganizationUnitModel {
                if ($this->tenants->lockById($tenantId) === null) {
                    throw OrganizationUnitException::tenantNotFound();
                }

                $limit = $this->entitlements->limit($tenantId, 'max_organization_units');
                if ($limit !== null && $this->units->countByTenant($tenantId) >= $limit) {
                    throw OrganizationUnitException::planLimitReached();
                }

                $created = $this->hierarchy->createUnit($tenantId, [
                    'type_id' => $this->requiredPositiveInt($payload['type_id'] ?? null, 'Organization-unit type'),
                    'parent_id' => $this->requiredPositiveInt($payload['parent_id'] ?? null, 'Parent organization unit'),
                    'name' => $this->domain->normalizeName((string) ($payload['name'] ?? '')),
                    'code' => $this->domain->normalizeKey((string) ($payload['code'] ?? '')),
                    'description' => $this->domain->normalizeOptionalText($this->nullableString($payload['description'] ?? null)),
                ]);
                $this->audit->unit('created', $created, null, $created->attributesToArray());

                return $created;
            }, 3);

            return Result::success($this->record($unit));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.create');
        }
    }

    /** @param array<string, mixed> $payload */
    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->scopedRecord($id);
            if ($existing === null) {
                return $this->notFound();
            }
            $before = $existing->toArray();
            $unit = DB::transaction(function () use ($existing, $payload, $before): OrganizationUnitModel {
                $updated = $this->hierarchy->updateUnit(
                    (int) $existing->require('tenant_id'),
                    (int) $existing->id(),
                    $this->requiredVersion($payload),
                    [
                        'type_id' => $payload['type_id'] ?? $existing->get('type_id'),
                        'parent_id' => $payload['parent_id'] ?? $existing->get('parent_id'),
                        'name' => array_key_exists('name', $payload)
                            ? $this->domain->normalizeName((string) $payload['name'])
                            : $existing->require('name'),
                        'code' => $existing->require('code'),
                        'description' => array_key_exists('description', $payload)
                            ? $this->domain->normalizeOptionalText($this->nullableString($payload['description']))
                            : $existing->get('description'),
                    ],
                );
                $this->audit->unit('updated', $updated, $before, $updated->attributesToArray());

                return $updated;
            }, 3);

            return Result::success($this->record($unit));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.update', ['organization_unit_id' => (string) $id]);
        }
    }

    public function activate(int|string $id, int $expectedVersion): Result
    {
        return $this->setActive($id, $expectedVersion, true);
    }

    public function deactivate(int|string $id, int $expectedVersion): Result
    {
        return $this->setActive($id, $expectedVersion, false);
    }

    public function retire(int|string $id, int $expectedVersion): Result
    {
        try {
            $existing = $this->scopedRecord($id);
            if ($existing === null) {
                return $this->notFound();
            }

            $before = $existing->toArray();
            $retired = DB::transaction(function () use ($existing, $expectedVersion, $before): OrganizationUnitModel {
                $updated = $this->hierarchy->retire(
                    (int) $existing->require('tenant_id'),
                    (int) $existing->id(),
                    $expectedVersion,
                );
                $this->audit->unit('retired', $updated, $before, $updated->attributesToArray());

                return $updated;
            }, 3);

            return Result::success($this->record($retired));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit.retire', ['organization_unit_id' => (string) $id]);
        }
    }

    public function replaceLogo(int|string $id, int $expectedVersion, string $temporaryPath): Result
    {
        $stored = null;
        $committed = false;

        try {
            $existing = $this->scopedRecord($id);
            if ($existing === null) {
                return $this->notFound();
            }
            $tenantId = (int) $existing->require('tenant_id');
            $unitId = (int) $existing->id();
            $oldObjectKey = $this->nullableString($existing->get('logo_object_key'));
            $stored = $this->assets->storeLogo($tenantId, $unitId, $temporaryPath);

            $updated = DB::transaction(function () use (
                $tenantId,
                $unitId,
                $expectedVersion,
                $stored,
                $oldObjectKey,
            ): OrganizationUnitModel {
                $unit = $this->hierarchy->replaceLogo(
                    $tenantId,
                    $unitId,
                    $expectedVersion,
                    $stored,
                );
                $this->assets->scheduleCleanup(
                    $tenantId,
                    $oldObjectKey,
                    'organization-unit logo replacement',
                );
                $this->audit->unit('logo_replaced', $unit, null, [
                    'id' => (int) $unit->getKey(),
                    'logo_mime_type' => $unit->getAttribute('logo_mime_type'),
                    'logo_size_bytes' => $unit->getAttribute('logo_size_bytes'),
                    'row_version' => (int) $unit->getAttribute('row_version'),
                ]);

                return $unit;
            }, 3);
            $committed = true;
            $this->assets->processCleanup($tenantId, $oldObjectKey);

            return Result::success($this->record($updated));
        } catch (Throwable $exception) {
            if (! $committed && is_array($stored)) {
                $this->assets->discardUnreferencedAsset(
                    $this->context->requireTenantId(),
                    $stored['object_key'] ?? null,
                    'failed organization-unit logo update',
                );
            }

            return $this->failure(
                $exception,
                'organization-unit.logo.replace',
                ['organization_unit_id' => (string) $id],
            );
        }
    }

    public function removeLogo(int|string $id, int $expectedVersion): Result
    {
        try {
            $existing = $this->scopedRecord($id);
            if ($existing === null) {
                return $this->notFound();
            }
            $tenantId = (int) $existing->require('tenant_id');
            $unitId = (int) $existing->id();
            $oldObjectKey = $this->nullableString($existing->get('logo_object_key'));

            $updated = DB::transaction(function () use (
                $tenantId,
                $unitId,
                $expectedVersion,
                $oldObjectKey,
            ): OrganizationUnitModel {
                $unit = $this->hierarchy->replaceLogo(
                    $tenantId,
                    $unitId,
                    $expectedVersion,
                    null,
                );
                $this->assets->scheduleCleanup(
                    $tenantId,
                    $oldObjectKey,
                    'organization-unit logo removal',
                );
                $this->audit->unit('logo_removed', $unit, null, [
                    'id' => (int) $unit->getKey(),
                    'logo_mime_type' => null,
                    'logo_size_bytes' => null,
                    'row_version' => (int) $unit->getAttribute('row_version'),
                ]);

                return $unit;
            }, 3);
            $this->assets->processCleanup($tenantId, $oldObjectKey);

            return Result::success($this->record($updated));
        } catch (Throwable $exception) {
            return $this->failure(
                $exception,
                'organization-unit.logo.remove',
                ['organization_unit_id' => (string) $id],
            );
        }
    }

    private function setActive(int|string $id, int $expectedVersion, bool $active): Result
    {
        try {
            $existing = $this->scopedRecord($id);
            if ($existing === null) {
                return $this->notFound();
            }
            $before = $existing->toArray();
            $updated = DB::transaction(function () use ($existing, $expectedVersion, $active, $before): OrganizationUnitModel {
                $changed = $this->hierarchy->setActive(
                    (int) $existing->require('tenant_id'),
                    (int) $existing->id(),
                    $expectedVersion,
                    $active,
                );
                $this->audit->unit($active ? 'activated' : 'deactivated', $changed, $before, $changed->attributesToArray());

                return $changed;
            }, 3);

            return Result::success($this->record($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception, $active ? 'organization-unit.activate' : 'organization-unit.deactivate', [
                'organization_unit_id' => (string) $id,
            ]);
        }
    }

    private function scopedRecord(int|string $id): ?DataRecord
    {
        $record = $this->units->findById($id);
        return $record !== null && (int) $record->get('tenant_id') === $this->context->requireTenantId()
            ? $record
            : null;
    }

    private function requiredVersion(array $payload): int
    {
        return $this->requiredPositiveInt($payload['expected_version'] ?? null, 'Expected version');
    }

    private function requiredPositiveInt(mixed $value, string $label): int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            throw new InvalidArgumentException($label.' is required.');
        }
        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function notFound(): Result
    {
        return Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization unit not found.'));
    }

    private function failure(Throwable $exception, string $operation, array $context = []): Result
    {
        if ($exception instanceof OrganizationUnitException) {
            return Result::failure(new Error(
                $exception->errorCode(),
                $exception->getMessage(),
                [...$exception->context(), 'operation' => $operation, ...$context],
            ));
        }

        return Result::failure($this->errors->normalize(
            $exception,
            OrganizationUnitErrorCode::INVALID_VALUE,
            ['operation' => $operation, ...$context],
        ));
    }

    private function record(OrganizationUnitModel $unit): DataRecord
    {
        $unit->load(['type:id,tenant_id,name,level,is_active', 'parent:id,tenant_id,name,code,path,depth,is_active,retired_at']);
        $payload = $unit->attributesToArray();
        $payload['type'] = $unit->type?->attributesToArray();
        $payload['parent'] = $unit->parent?->attributesToArray();
        $payload['has_logo'] = is_string($unit->getAttribute('logo_object_key'))
            && trim((string) $unit->getAttribute('logo_object_key')) !== '';
        unset($payload['logo_object_key'], $payload['path_hash']);

        return new DataRecord($payload);
    }
}
