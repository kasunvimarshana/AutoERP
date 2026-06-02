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
                $records[] = $this->ownershipRecord($model);
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

        return $model instanceof Model ? $this->ownershipRecord($model) : null;
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

        return $model instanceof Model ? $this->ownershipRecord($model) : null;
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

    private function ownershipRecord(Model $model): DataRecord
    {
        $data = $model->toArray();
        $data['owner_display_name'] = $this->ownerDisplayName(
            (string) ($data['owner_type'] ?? ''),
            isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            (string) ($data['owner_name'] ?? ''),
            isset($data['party_id']) ? (int) $data['party_id'] : null,
            (int) ($data['tenant_id'] ?? 0),
        );

        return new DataRecord($data);
    }

    private function ownerDisplayName(string $ownerType, ?int $ownerId, string $ownerName, ?int $partyId, int $tenantId): string
    {
        if (trim($ownerName) !== '') {
            return trim($ownerName);
        }

        $table = match ($ownerType) {
            'customer' => 'customers',
            'supplier', 'provider' => 'suppliers',
            'employee' => 'employees',
            'party' => 'parties',
            default => null,
        };

        $lookupId = $ownerType === 'party' ? $partyId : $ownerId;
        if ($table === null || $lookupId === null || $lookupId < 1 || ! Schema::hasTable($table)) {
            return match ($ownerType) {
                'company' => 'Internal company',
                'external_party' => 'External party',
                default => ucfirst(str_replace('_', ' ', $ownerType ?: 'owner')),
            };
        }

        $row = DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where('id', $lookupId)
            ->first();

        if ($row === null) {
            return ucfirst(str_replace('_', ' ', $ownerType));
        }

        $code = match ($table) {
            'customers' => $row->customer_code ?? null,
            'suppliers' => $row->supplier_code ?? null,
            default => $row->code ?? null,
        };
        $name = match ($table) {
            'customers' => $row->display_name ?? $row->customer_name ?? null,
            'suppliers' => $row->display_name ?? $row->supplier_name ?? null,
            default => $row->display_name ?? $row->name ?? null,
        };

        return trim(implode(' - ', array_filter([(string) $code, (string) $name]))) ?: ucfirst(str_replace('_', ' ', $ownerType));
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
