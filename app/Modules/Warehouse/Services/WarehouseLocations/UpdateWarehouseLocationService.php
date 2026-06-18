<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\WarehouseLocations;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class UpdateWarehouseLocationService
{
    public function __construct(private readonly WarehouseDomainService $domain) {}

    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId, array $payload): Result
    {
        try {
            $preflight = WarehouseLocationModel::query()
                ->inExactScope($tenantId, $organizationUnitId)
                ->find($id);
            if (! $preflight instanceof WarehouseLocationModel) {
                return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
            }

            $wantsDefault = array_key_exists('is_default', $payload) && (bool) $payload['is_default'];
            $transaction = DB::transaction(function () use ($id, $tenantId, $organizationUnitId, $payload, $preflight, $wantsDefault): WarehouseLocationModel|Result {
                if ($wantsDefault || (! array_key_exists('is_active', $payload) ? false : ! (bool) $payload['is_active'])) {
                    WarehouseModel::query()
                        ->whereKey((int) $preflight->warehouse_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $location = WarehouseLocationModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $location instanceof WarehouseLocationModel) {
                    return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
                }

                $expectedVersion = (int) ($payload['row_version'] ?? 0);
                if ($expectedVersion !== (int) $location->row_version) {
                    return Result::failure(new Error(WarehouseErrorCode::STALE_RECORD, 'Warehouse location was changed by someone else. Reload before saving.'));
                }

                if (array_key_exists('warehouse_id', $payload) && (int) $payload['warehouse_id'] !== (int) $location->warehouse_id) {
                    throw new InvalidArgumentException('Warehouse location cannot be moved to another warehouse through ordinary edit.');
                }

                $warehouse = WarehouseModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey((int) $location->warehouse_id)
                    ->firstOrFail();

                $name = array_key_exists('name', $payload) ? trim((string) $payload['name']) : (string) $location->name;
                $code = array_key_exists('code', $payload)
                    ? $this->domain->nullableString($payload['code'])
                    : $this->domain->nullableString($location->code);
                $parentId = array_key_exists('parent_id', $payload)
                    ? $this->domain->nullableInt($payload['parent_id'])
                    : $this->domain->nullableInt($location->parent_id);
                $parent = $this->domain->resolveParent($warehouse, $parentId, (int) $location->getKey());
                $hierarchy = $this->domain->hierarchyAttributes($parent, $name, $code);
                $oldPath = (string) $location->path;
                $oldDepth = (int) $location->depth;

                $isActive = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : (bool) $location->is_active;
                $isDefault = array_key_exists('is_default', $payload) ? (bool) $payload['is_default'] : (bool) $location->is_default;
                if (! $isActive) {
                    $isDefault = false;
                }
                $this->domain->assertDefaultIsActive($isDefault, $isActive, 'Default warehouse location');
                $this->domain->assertLocationUnique($tenantId, (int) $warehouse->getKey(), $name, $code, (int) $location->getKey());

                $type = array_key_exists('type', $payload) ? (string) ($payload['type'] ?? 'bin') : (string) $location->type;
                if ($type === 'bin' && $location->children()->exists()) {
                    throw new InvalidArgumentException('Locations with child locations cannot be changed to Bin type.');
                }

                if ($isDefault) {
                    $this->domain->clearOtherLocationDefaults($location);
                }

                $attributes = [
                    'parent_id' => $parent?->getKey(),
                    'name' => $name,
                    'code' => $code,
                    'path' => $hierarchy['path'],
                    'depth' => $hierarchy['depth'],
                    'type' => $type,
                    'is_active' => $isActive,
                    'is_pickable' => array_key_exists('is_pickable', $payload) ? (bool) $payload['is_pickable'] : (bool) $location->is_pickable,
                    'is_receivable' => array_key_exists('is_receivable', $payload) ? (bool) $payload['is_receivable'] : (bool) $location->is_receivable,
                    'is_default' => $isDefault,
                    'capacity' => array_key_exists('capacity', $payload) ? $payload['capacity'] : $location->capacity,
                    'row_version' => ((int) $location->row_version) + 1,
                ];
                if (array_key_exists('metadata', $payload)) {
                    $attributes['metadata'] = $payload['metadata'];
                }

                $location->fill($attributes);
                $location->save();
                $this->domain->updateDescendantHierarchy($location, $oldPath, $oldDepth);

                return $location->refresh()->load(['warehouse', 'parent', 'organizationUnit']);
            }, 3);

            return $transaction instanceof Result ? $transaction : Result::success($transaction);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_HIERARCHY, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse location could not be updated.'));
        }
    }

    public function setActive(int|string $id, int $tenantId, ?int $organizationUnitId, bool $isActive): Result
    {
        try {
            $preflight = WarehouseLocationModel::query()
                ->inExactScope($tenantId, $organizationUnitId)
                ->find($id);
            if (! $preflight instanceof WarehouseLocationModel) {
                return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
            }

            $transaction = DB::transaction(function () use ($id, $tenantId, $organizationUnitId, $isActive, $preflight): WarehouseLocationModel|Result {
                if (! $isActive) {
                    WarehouseModel::query()
                        ->whereKey((int) $preflight->warehouse_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $location = WarehouseLocationModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $location instanceof WarehouseLocationModel) {
                    return Result::failure(new Error(WarehouseErrorCode::LOCATION_NOT_FOUND, 'Warehouse location not found.'));
                }

                $location->is_active = $isActive;
                if (! $isActive) {
                    $location->is_default = false;
                }
                $location->row_version = ((int) $location->row_version) + 1;
                $location->save();

                return $location->refresh()->load(['warehouse', 'parent', 'organizationUnit']);
            }, 3);

            return $transaction instanceof Result ? $transaction : Result::success($transaction);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse location status could not be changed.'));
        }
    }
}
