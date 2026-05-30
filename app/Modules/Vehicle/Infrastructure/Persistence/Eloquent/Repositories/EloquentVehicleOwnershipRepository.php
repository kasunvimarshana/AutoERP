<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Vehicle\Application\Repositories\VehicleOwnershipRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleOwnershipModel;

final class EloquentVehicleOwnershipRepository extends EloquentRepository implements VehicleOwnershipRepositoryInterface
{
    public function __construct(VehicleOwnershipModel $model)
    {
        parent::__construct($model);
    }

    public function listForVehicle(int $tenantId, int $vehicleId): array
    {
        $records = [];
        $models = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function findForVehicle(int $tenantId, int $vehicleId, int $ownershipId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('id', $ownershipId)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function currentForVehicleRole(int $tenantId, int $vehicleId, string $ownershipRole): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('ownership_role', $ownershipRole)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function vehicleExistsInTenant(int $tenantId, int $vehicleId): bool
    {
        return DB::table('vehicles')
            ->where('tenant_id', $tenantId)
            ->where('id', $vehicleId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function ownerReferenceExists(string $ownerType, int $ownerId, int $tenantId): bool
    {
        $table = match ($ownerType) {
            'customer' => 'customers',
            'supplier' => 'suppliers',
            'provider' => 'suppliers',
            'employee' => 'employees',
            'party' => 'parties',
            default => null,
        };

        if ($table === null || ! Schema::hasTable($table)) {
            return false;
        }

        $query = DB::table($table)
            ->where('id', $ownerId)
            ->where('tenant_id', $tenantId);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    public function clearCurrentRole(int $tenantId, int $vehicleId, string $ownershipRole, ?int $exceptOwnershipId = null): void
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('ownership_role', $ownershipRole)
            ->where('is_current', true);

        if ($exceptOwnershipId !== null) {
            $query->where('id', '!=', $exceptOwnershipId);
        }

        $query->update([
            'is_current' => false,
            'end_date' => now()->toDateString(),
            'updated_at' => now(),
        ]);
    }
}
