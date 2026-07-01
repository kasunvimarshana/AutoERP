<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class UpdateWarehouseService
{
    public function __construct(private readonly WarehouseDomainService $domain) {}

    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId, array $payload): Result
    {
        try {
            $transaction = DB::transaction(function () use ($id, $tenantId, $organizationUnitId, $payload): WarehouseModel|Result {
                $wantsDefault = array_key_exists('is_default', $payload) && (bool) $payload['is_default'];
                if ($wantsDefault) {
                    $this->domain->lockScopeOwner($tenantId, $organizationUnitId);
                }

                $warehouse = WarehouseModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $warehouse instanceof WarehouseModel) {
                    return $this->missingResult($id, $tenantId);
                }

                $expectedVersion = (int) ($payload['row_version'] ?? 0);
                if ($expectedVersion !== (int) $warehouse->row_version) {
                    return Result::failure(new Error(WarehouseErrorCode::STALE_RECORD, 'Warehouse was changed by someone else. Reload before saving.'));
                }

                $name = array_key_exists('name', $payload) ? trim((string) $payload['name']) : (string) $warehouse->name;
                $code = array_key_exists('code', $payload)
                    ? $this->domain->nullableString($payload['code'])
                    : $this->domain->nullableString($warehouse->code);
                $isActive = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : (bool) $warehouse->is_active;
                $isDefault = array_key_exists('is_default', $payload) ? (bool) $payload['is_default'] : (bool) $warehouse->is_default;
                if (! $isActive) {
                    $isDefault = false;
                }
                $this->domain->assertDefaultIsActive($isDefault, $isActive, 'Default warehouse');
                $this->domain->assertWarehouseUnique($tenantId, $organizationUnitId, $name, $code, (int) $warehouse->getKey());

                if ($isDefault) {
                    $this->domain->clearOtherWarehouseDefaults($warehouse);
                }

                $attributes = [
                    'name' => $name,
                    'code' => $code,
                    'type' => array_key_exists('type', $payload) ? ($payload['type'] ?? 'standard') : $warehouse->type,
                    'is_active' => $isActive,
                    'is_default' => $isDefault,
                    'row_version' => ((int) $warehouse->row_version) + 1,
                ];
                if (array_key_exists('metadata', $payload)) {
                    $attributes['metadata'] = $payload['metadata'];
                }

                $warehouse->fill($attributes);
                $warehouse->save();

                return $warehouse->refresh()->load(['organizationUnit', 'defaultLocation'])->loadCount('locations');
            }, 3);

            return $transaction instanceof Result ? $transaction : Result::success($transaction);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse could not be updated.'));
        }
    }

    public function setActive(int|string $id, int $tenantId, ?int $organizationUnitId, bool $isActive): Result
    {
        try {
            $transaction = DB::transaction(function () use ($id, $tenantId, $organizationUnitId, $isActive): WarehouseModel|Result {
                if (! $isActive) {
                    $this->domain->lockScopeOwner($tenantId, $organizationUnitId);
                }

                $warehouse = WarehouseModel::query()
                    ->inExactScope($tenantId, $organizationUnitId)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();
                if (! $warehouse instanceof WarehouseModel) {
                    return $this->missingResult($id, $tenantId);
                }

                $warehouse->is_active = $isActive;
                if (! $isActive) {
                    $warehouse->is_default = false;
                }
                $warehouse->row_version = ((int) $warehouse->row_version) + 1;
                $warehouse->save();

                return $warehouse->refresh()->load(['organizationUnit', 'defaultLocation'])->loadCount('locations');
            }, 3);

            return $transaction instanceof Result ? $transaction : Result::success($transaction);
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse status could not be changed.'));
        }
    }

    private function missingResult(int|string $id, int $tenantId): Result
    {
        $owner = DB::table('warehouses')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first(['tenant_id']);

        if ($owner !== null && (int) $owner->tenant_id !== $tenantId) {
            return Result::failure(new Error(WarehouseErrorCode::SCOPE_MISMATCH, 'Warehouse belongs to another tenant.'));
        }

        return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
    }
}
