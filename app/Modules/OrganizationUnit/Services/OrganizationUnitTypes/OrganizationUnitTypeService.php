<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\OrganizationUnitTypes;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;
use Modules\OrganizationUnit\Exceptions\OrganizationUnitException;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Models\OrganizationUnitTypeModel;
use Modules\OrganizationUnit\Services\Audit\OrganizationUnitAuditService;
use Modules\OrganizationUnit\Services\Contracts\OrganizationUnitDomainServiceInterface;
use Modules\OrganizationUnit\Support\OrganizationUnitContext;
use Modules\OrganizationUnit\Support\OrganizationUnitNameKey;
use Throwable;

final class OrganizationUnitTypeService
{
    public function __construct(
        private readonly OrganizationUnitTypeModel $types,
        private readonly OrganizationUnitModel $units,
        private readonly OrganizationUnitDomainServiceInterface $domain,
        private readonly OrganizationUnitContext $context,
        private readonly OrganizationUnitAuditService $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    public function list(): Result
    {
        try {
            return Result::success($this->types->newQuery()
                ->where('tenant_id', $this->context->requireTenantId())
                ->orderBy('level')
                ->orderBy('name')
                ->get()
                ->map(fn (OrganizationUnitTypeModel $type): DataRecord => $this->record($type))
                ->values()
                ->all());
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-type.list');
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $type = $this->findScoped($id);
            return $type instanceof OrganizationUnitTypeModel
                ? Result::success($this->record($type))
                : Result::failure(new Error(OrganizationUnitErrorCode::NOT_FOUND, 'Organization-unit type not found.'));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-type.get');
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            $name = $this->domain->normalizeName((string) ($payload['name'] ?? ''));
            $nameKey = OrganizationUnitNameKey::from($name);
            $level = $this->domain->normalizeLevel((int) ($payload['level'] ?? -1));
            if ($this->types->newQuery()->where('tenant_id', $tenantId)->where('name_key', $nameKey)->exists()) {
                return Result::failure(new Error(OrganizationUnitErrorCode::CONFLICT, 'Type name already exists for this tenant.'));
            }

            $type = DB::transaction(function () use ($tenantId, $name, $nameKey, $level, $payload): OrganizationUnitTypeModel {
                $created = new OrganizationUnitTypeModel();
                $created->forceFill([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'name_key' => $nameKey,
                    'level' => $level,
                    'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true,
                    'row_version' => 1,
                ])->save();
                $this->audit->type('created', $created, null, $created->attributesToArray());

                return $created->refresh();
            }, 3);

            return Result::success($this->record($type));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-type.create');
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            $expectedVersion = (int) ($payload['expected_version'] ?? 0);
            if ($expectedVersion < 1) {
                throw OrganizationUnitException::invalid('The current type version is required.');
            }

            $updated = DB::transaction(function () use ($tenantId, $id, $payload, $expectedVersion): OrganizationUnitTypeModel {
                $type = $this->types->newQuery()->where('tenant_id', $tenantId)->whereKey($id)->lockForUpdate()->first();
                if (! $type instanceof OrganizationUnitTypeModel) {
                    throw OrganizationUnitException::notFound('Organization-unit type not found.');
                }
                if ((int) $type->getAttribute('row_version') !== $expectedVersion) {
                    throw OrganizationUnitException::versionConflict('Organization-unit type changed since it was loaded. Refresh and try again.');
                }

                $before = $type->attributesToArray();
                $name = array_key_exists('name', $payload)
                    ? $this->domain->normalizeName((string) $payload['name'])
                    : (string) $type->getAttribute('name');
                $nameKey = OrganizationUnitNameKey::from($name);
                $duplicate = $this->types->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('name_key', $nameKey)
                    ->whereKeyNot($id)
                    ->exists();
                if ($duplicate) {
                    throw OrganizationUnitException::conflict('Type name already exists for this tenant.');
                }

                $level = array_key_exists('level', $payload)
                    ? $this->domain->normalizeLevel((int) $payload['level'])
                    : (int) $type->getAttribute('level');
                $incompatibleUnits = $this->units->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('type_id', $type->getKey())
                    ->where('depth', '!=', $level)
                    ->count();
                if ($incompatibleUnits > 0) {
                    throw OrganizationUnitException::lifecycleBlocked('Move or reclassify organization units before changing the type hierarchy level.');
                }

                $active = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : (bool) $type->getAttribute('is_active');
                if (! $active && $this->units->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('type_id', $type->getKey())
                    ->where('is_active', true)
                    ->whereNull('retired_at')
                    ->exists()) {
                    throw OrganizationUnitException::lifecycleBlocked('Deactivate or reclassify active organization units before deactivating this type.');
                }

                $type->forceFill([
                    'name' => $name,
                    'name_key' => $nameKey,
                    'level' => $level,
                    'is_active' => $active,
                    'row_version' => $expectedVersion + 1,
                ])->save();
                $type->refresh();
                $this->audit->type('updated', $type, $before, $type->attributesToArray());

                return $type;
            }, 3);

            return Result::success($this->record($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-type.update');
        }
    }

    public function delete(int|string $id, int $expectedVersion): Result
    {
        try {
            $tenantId = $this->context->requireTenantId();
            DB::transaction(function () use ($tenantId, $id, $expectedVersion): void {
                $type = $this->types->newQuery()->where('tenant_id', $tenantId)->whereKey($id)->lockForUpdate()->first();
                if (! $type instanceof OrganizationUnitTypeModel) {
                    throw OrganizationUnitException::notFound('Organization-unit type not found.');
                }
                if ((int) $type->getAttribute('row_version') !== $expectedVersion) {
                    throw OrganizationUnitException::versionConflict('Organization-unit type changed since it was loaded. Refresh and try again.');
                }
                if ($this->units->newQuery()->where('tenant_id', $tenantId)->where('type_id', $type->getKey())->exists()) {
                    throw OrganizationUnitException::lifecycleBlocked('An organization-unit type in use cannot be deleted. Deactivate it after reclassifying all units.');
                }
                $before = $type->attributesToArray();
                $this->audit->type('deleted', $type, $before, null);
                $type->delete();
            }, 3);

            return Result::success(true);
        } catch (Throwable $exception) {
            return $this->failure($exception, 'organization-unit-type.delete');
        }
    }

    private function findScoped(int|string $id): ?OrganizationUnitTypeModel
    {
        return $this->types->newQuery()->where('tenant_id', $this->context->requireTenantId())->whereKey($id)->first();
    }

    private function record(OrganizationUnitTypeModel $type): DataRecord
    {
        $payload = $type->attributesToArray();
        $payload['organization_unit_count'] = $this->units->newQuery()
            ->where('tenant_id', (int) $type->getAttribute('tenant_id'))
            ->where('type_id', $type->getKey())
            ->count();
        unset($payload['name_key']);
        return new DataRecord($payload);
    }

    private function failure(Throwable $exception, string $operation): Result
    {
        if ($exception instanceof OrganizationUnitException) {
            return Result::failure(new Error(
                $exception->errorCode(),
                $exception->getMessage(),
                [...$exception->context(), 'operation' => $operation],
            ));
        }

        return Result::failure($this->errors->normalize(
            $exception,
            OrganizationUnitErrorCode::INVALID_VALUE,
            ['operation' => $operation],
        ));
    }
}
