<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['make', 'model', 'type', 'category', 'currentOwnerships']);
        $this->applyCriteria($query, $criteria);
        $sort = in_array(($criteria['sort'] ?? null), ['vehicle_number', 'code', 'registration_number', 'status', 'created_at'], true) ? (string) $criteria['sort'] : 'vehicle_number';
        $direction = ($criteria['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, string $kind): LengthAwarePaginator
    {
        if ($kind === 'active') {
            $criteria['status'] = VehicleStatus::Active->value;
        } elseif ($kind === 'by-customer' && empty($criteria['customer_id'])) {
            throw new \InvalidArgumentException('Customer is required for vehicle by-customer lookup.');
        } elseif ($kind === 'service-available') {
            $criteria['available_for_service'] = true;
        }

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->with([
                'make',
                'model',
                'type',
                'category',
                'documents',
                'attributes',
                'ownerships',
                'currentOwnerships',
                'statusHistories',
            ])
            ->findOrFail($id);
    }

    public function vehicle(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(Vehicle $vehicle): void
    {
        $this->assertUnused($vehicle);
        $vehicle->delete();
    }

    private function assertUnused(Vehicle $vehicle): void
    {
        $tables = [
            'vehicle_service_jobs' => 'vehicle_id',
            'vehicle_rental_owner_agreements' => 'vehicle_id',
            'vehicle_rental_uses' => 'vehicle_id',
            'vehicle_rental_finance_agreements' => 'vehicle_id',
            'vehicle_rental_expenses' => 'vehicle_id',
            'vehicle_rental_reservations' => 'requested_vehicle_id',
            'promotion_cards' => 'vehicle_id',
            'vehicle_ownerships' => 'vehicle_id',
        ];

        foreach ($tables as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)
                ->where('tenant_id', (int) $vehicle->tenant_id)
                ->where($column, (int) $vehicle->getKey());

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($query->exists()) {
                throw new ConflictHttpException('Vehicle is referenced by operational or financial history and cannot be deleted. Deactivate the vehicle instead.');
            }
        }
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Vehicle::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $query->matchingSearch($criteria['search'] ?? null);
        foreach (['status', 'vehicle_make_id', 'vehicle_model_id', 'vehicle_type_id', 'vehicle_category_id'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['customer_id'])) {
            $query->whereCurrentOwner('customer', (int) $criteria['customer_id']);
        }
        if (($criteria['ownership_scope'] ?? null) === 'customer') {
            $query->whereCurrentOwner('customer');
        }
        if (($criteria['ownership_scope'] ?? null) === 'supplier') {
            $query->whereCurrentOwner('supplier');
        }
        if (($criteria['ownership_scope'] ?? null) === 'company') {
            $query->whereCurrentOwner('company');
        }
        if (! empty($criteria['available_for_service'])) {
            $query->whereIn('status', [VehicleStatus::Active->value, VehicleStatus::UnderService->value]);
        }
    }
}
