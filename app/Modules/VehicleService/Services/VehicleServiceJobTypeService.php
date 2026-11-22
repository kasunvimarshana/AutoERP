<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleService\DTOs\VehicleServiceJobTypeData;
use Modules\VehicleService\Enums\VehicleServiceJobType as VehicleServiceJobTypeEnum;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobType;

final class VehicleServiceJobTypeService
{
    public function ensureDefaults(int $tenantId, ?int $organizationUnitId = null): void
    {
        $hasAny = VehicleServiceJobType::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->exists();

        if ($hasAny) {
            return;
        }

        DB::transaction(function () use ($tenantId, $organizationUnitId): void {
            foreach (VehicleServiceJobTypeEnum::defaults() as $default) {
                VehicleServiceJobType::query()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'code' => $default['code'],
                    'name' => $default['name'],
                    'description' => $default['description'],
                    'tracks_mileage' => $default['tracks_mileage'],
                    'is_active' => $default['is_active'],
                    'is_system' => $default['is_system'],
                    'sort_order' => $default['sort_order'],
                    'row_version' => 1,
                ]);
            }
        });
    }

    public function resolveForJob(
        int $tenantId,
        ?int $jobTypeId,
        string $typeCode,
        bool $isNewAssignment = true,
    ): VehicleServiceJobType {
        $typeCode = trim($typeCode);
        $jobType = null;

        if ($jobTypeId !== null && $jobTypeId > 0) {
            $jobType = VehicleServiceJobType::query()
                ->where('tenant_id', $tenantId)
                ->find($jobTypeId);
        }

        if ($jobType === null && $typeCode !== '') {
            $jobType = VehicleServiceJobType::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $typeCode)
                ->first();
        }

        if ($jobType === null) {
            $this->ensureDefaults($tenantId);

            if ($jobTypeId !== null && $jobTypeId > 0) {
                $jobType = VehicleServiceJobType::query()
                    ->where('tenant_id', $tenantId)
                    ->find($jobTypeId);
            }

            if ($jobType === null && $typeCode !== '') {
                $jobType = VehicleServiceJobType::query()
                    ->where('tenant_id', $tenantId)
                    ->where('code', $typeCode)
                    ->first();
            }
        }

        if ($jobType === null) {
            throw new InvalidArgumentException('Selected service job type is invalid.');
        }

        if ($isNewAssignment && ! $jobType->is_active) {
            throw new InvalidArgumentException("Service job type '{$jobType->name}' is inactive and cannot be assigned.");
        }

        return $jobType;
    }

    public function create(VehicleServiceJobTypeData $data): VehicleServiceJobType
    {
        $this->ensureDefaults($data->tenantId, $data->organizationUnitId);
        $this->validateBasicData($data);
        $this->assertCodeUnique($data->tenantId, $data->code);

        return DB::transaction(function () use ($data): VehicleServiceJobType {
            return VehicleServiceJobType::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'code' => $data->code,
                'name' => $data->name,
                'description' => $data->description,
                'tracks_mileage' => $data->tracksMileage,
                'is_active' => $data->isActive,
                'is_system' => false,
                'sort_order' => $data->sortOrder,
                'created_by' => $data->actorId,
                'updated_by' => $data->actorId,
                'row_version' => 1,
            ]);
        });
    }

    public function update(VehicleServiceJobType $jobType, VehicleServiceJobTypeData $data): VehicleServiceJobType
    {
        if ((int) $jobType->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Service job type belongs to a different tenant.');
        }

        $this->validateBasicData($data);

        return DB::transaction(function () use ($jobType, $data): VehicleServiceJobType {
            /** @var VehicleServiceJobType $locked */
            $locked = VehicleServiceJobType::query()
                ->where('tenant_id', $data->tenantId)
                ->lockForUpdate()
                ->findOrFail($jobType->getKey());

            if ($data->expectedVersion !== null && (int) $locked->row_version !== $data->expectedVersion) {
                throw new InvalidArgumentException('Service job type has been modified by another transaction.');
            }

            $updatePayload = [
                'organization_unit_id' => $data->organizationUnitId,
                'name' => $data->name,
                'description' => $data->description,
                'tracks_mileage' => $data->tracksMileage,
                'is_active' => $data->isActive,
                'sort_order' => $data->sortOrder,
                'updated_by' => $data->actorId,
            ];

            if ($locked->is_system) {
                if ($data->code !== '' && $data->code !== $locked->code) {
                    throw new InvalidArgumentException('System service job type code cannot be modified.');
                }
            } else {
                if ($data->code !== '' && $data->code !== $locked->code) {
                    $this->assertCodeUnique($data->tenantId, $data->code, (int) $locked->getKey());
                    $updatePayload['code'] = $data->code;
                }
            }

            $locked->fill($updatePayload)->save();

            return $locked->refresh();
        });
    }

    public function delete(VehicleServiceJobType $jobType, ?int $expectedVersion = null): void
    {
        DB::transaction(function () use ($jobType, $expectedVersion): void {
            /** @var VehicleServiceJobType $locked */
            $locked = VehicleServiceJobType::query()
                ->where('tenant_id', $jobType->tenant_id)
                ->lockForUpdate()
                ->findOrFail($jobType->getKey());

            if ($expectedVersion !== null && (int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Service job type has been modified by another transaction.');
            }

            if ($locked->is_system) {
                throw new InvalidArgumentException('System service job types cannot be deleted.');
            }

            $hasReferencingJobs = $locked->jobs()->exists()
                || VehicleServiceJob::query()
                    ->where('tenant_id', $locked->tenant_id)
                    ->where('type', $locked->code)
                    ->exists();

            if ($hasReferencingJobs) {
                throw new InvalidArgumentException('Service job type cannot be deleted while service jobs reference it.');
            }

            $locked->delete();
        });
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId = null): VehicleServiceJobType
    {
        $this->ensureDefaults($tenantId, $organizationUnitId);

        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function paginate(
        array $criteria,
        int $tenantId,
        ?int $organizationUnitId,
        int $perPage,
    ): LengthAwarePaginator {
        $this->ensureDefaults($tenantId, $organizationUnitId);

        return $this->criteria($this->baseQuery($tenantId, $organizationUnitId), $criteria)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function lookup(
        array $criteria,
        int $tenantId,
        ?int $organizationUnitId,
        int $perPage,
    ): LengthAwarePaginator {
        $criteria['is_active'] = true;

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 100));
    }

    private function validateBasicData(VehicleServiceJobTypeData $data): void
    {
        if (trim($data->name) === '') {
            throw new InvalidArgumentException('Service job type name is required.');
        }

        if (trim($data->code) === '') {
            throw new InvalidArgumentException('Service job type code is required.');
        }

        if (! preg_match('/^[a-z0-9_-]+$/i', $data->code)) {
            throw new InvalidArgumentException('Service job type code must contain only letters, numbers, dashes, and underscores.');
        }

        if ($data->sortOrder < 0) {
            throw new InvalidArgumentException('Service job type sort order cannot be negative.');
        }
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return VehicleServiceJobType::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId !== null, fn (Builder $query) => $query->where(
                fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId),
            ));
    }

    private function criteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn (Builder $scope) => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        if (array_key_exists('is_active', $criteria) && $criteria['is_active'] !== null && $criteria['is_active'] !== '') {
            $query->where('is_active', (bool) $criteria['is_active']);
        }

        return $query;
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = VehicleServiceJobType::query()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('code', $code);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('A service job type with this code already exists.');
        }
    }
}
