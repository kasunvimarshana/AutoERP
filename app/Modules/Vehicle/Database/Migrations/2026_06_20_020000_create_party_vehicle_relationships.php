<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCustomerVehicles();
        $this->createSupplierVehicles();
        $this->addCheckConstraints();

        if (Schema::hasTable('vehicle_ownerships')) {
            $this->migratePartyRows('customer', 'customer_vehicles', 'customer_id');
            $this->migratePartyRows(['supplier', 'owner'], 'supplier_vehicles', 'supplier_id');
        }
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->restorePartyRows('customer_vehicles', 'customer_id', 'customer');
        $this->restorePartyRows('supplier_vehicles', 'supplier_id', 'supplier');
        Schema::dropIfExists('supplier_vehicles');
        Schema::dropIfExists('customer_vehicles');
    }

    private function createCustomerVehicles(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('relationship_type')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'current_guard'], 'customer_vehicles_one_current_uk');
            $table->unique(['vehicle_id', 'customer_id', 'active_guard'], 'customer_vehicles_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'customer_vehicles_scope_vehicle_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'customer_id'], 'customer_vehicles_scope_customer_idx');
            $table->index(['vehicle_id', 'is_current'], 'customer_vehicles_current_idx');
            $table->index(['started_at', 'ended_at'], 'customer_vehicles_dates_idx');
        });
    }

    private function createSupplierVehicles(): void
    {
        Schema::create('supplier_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('relationship_type')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'current_guard'], 'supplier_vehicles_one_current_uk');
            $table->unique(['vehicle_id', 'supplier_id', 'active_guard'], 'supplier_vehicles_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'supplier_vehicles_scope_vehicle_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'supplier_id'], 'supplier_vehicles_scope_supplier_idx');
            $table->index(['vehicle_id', 'is_current'], 'supplier_vehicles_current_idx');
            $table->index(['started_at', 'ended_at'], 'supplier_vehicles_dates_idx');
        });
    }

    private function addCheckConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }
        $true = $driver === 'pgsql' ? 'TRUE' : '1';
        $false = $driver === 'pgsql' ? 'FALSE' : '0';
        foreach (['customer_vehicles', 'supplier_vehicles'] as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_dates_ck CHECK (ended_at IS NULL OR ended_at >= started_at)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_current_end_ck CHECK (is_current = {$false} OR ended_at IS NULL)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_current_guard_ck CHECK ((is_current = {$true} AND current_guard = 1) OR (is_current = {$false} AND current_guard IS NULL))");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_active_guard_ck CHECK ((ended_at IS NULL AND active_guard = 1) OR (ended_at IS NOT NULL AND active_guard IS NULL))");
        }
    }

    /** @param string|list<string> $ownerTypes */
    private function migratePartyRows(string|array $ownerTypes, string $targetTable, string $partyColumn): void
    {
        $types = (array) $ownerTypes;
        $partyTable = $partyColumn === 'customer_id' ? 'customers' : 'suppliers';
        $rows = DB::table('vehicle_ownerships')->whereIn('owner_type', $types)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            $vehicle = DB::table('vehicles')->where('id', $row->vehicle_id)->first();
            $party = $row->owner_id === null ? null : DB::table($partyTable)->where('id', $row->owner_id)->first();
            if ($vehicle === null || $party === null
                || (int) $vehicle->tenant_id !== (int) $row->tenant_id
                || (int) $party->tenant_id !== (int) $row->tenant_id
                || ! $this->organizationCompatible($vehicle->organization_unit_id, $party->organization_unit_id)) {
                throw new RuntimeException("Cannot migrate invalid vehicle ownership row {$row->id}; repair its party and scope references first.");
            }
        }

        $normalized = $this->normalizeRows($rows);
        DB::transaction(function () use ($normalized, $targetTable, $partyColumn): void {
            foreach ($normalized as $row) {
                DB::table($targetTable)->insert([
                    'tenant_id' => $row->tenant_id,
                    'organization_unit_id' => $row->organization_unit_id,
                    $partyColumn => $row->owner_id,
                    'vehicle_id' => $row->vehicle_id,
                    'relationship_type' => $row->ownership_type,
                    'started_at' => $row->started_at,
                    'ended_at' => $row->ended_at,
                    'is_current' => (bool) $row->is_current,
                    'current_guard' => $row->is_current ? 1 : null,
                    'active_guard' => $row->ended_at === null ? 1 : null,
                    'notes' => $row->notes,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            DB::table('vehicle_ownerships')->whereIn('id', $normalized->pluck('id'))->delete();
        });
    }

    /** @param Collection<int,object> $rows @return Collection<int,object> */
    private function normalizeRows(Collection $rows): Collection
    {
        $rows->groupBy(fn (object $row): string => $row->vehicle_id.':'.$row->owner_id)
            ->each(function (Collection $pair): void {
                $active = $pair->whereNull('ended_at')->sortByDesc(fn (object $row): string => $row->started_at.'-'.str_pad((string) $row->id, 20, '0', STR_PAD_LEFT));
                $winner = $active->first();
                foreach ($active->skip(1) as $duplicate) {
                    $duplicate->ended_at = $winner->started_at;
                    $duplicate->is_current = false;
                }
            });

        $rows->groupBy('vehicle_id')->each(function (Collection $vehicleRows): void {
            $current = $vehicleRows->filter(fn (object $row): bool => (bool) $row->is_current && $row->ended_at === null)
                ->sortByDesc(fn (object $row): string => $row->started_at.'-'.str_pad((string) $row->id, 20, '0', STR_PAD_LEFT));
            foreach ($current->skip(1) as $duplicate) {
                $duplicate->is_current = false;
            }
            foreach ($vehicleRows->whereNotNull('ended_at') as $ended) {
                $ended->is_current = false;
            }
        });

        return $rows;
    }

    private function organizationCompatible(mixed $vehicleOrganization, mixed $partyOrganization): bool
    {
        return $partyOrganization === null
            || ($vehicleOrganization !== null && (int) $vehicleOrganization === (int) $partyOrganization);
    }

    private function restorePartyRows(string $sourceTable, string $partyColumn, string $ownerType): void
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable('vehicle_ownerships')) {
            return;
        }
        foreach (DB::table($sourceTable)->orderBy('id')->get() as $row) {
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $row->tenant_id,
                'organization_unit_id' => $row->organization_unit_id,
                'vehicle_id' => $row->vehicle_id,
                'owner_type' => $ownerType,
                'owner_id' => $row->{$partyColumn},
                'ownership_type' => $row->relationship_type ?? ($ownerType === 'customer' ? 'customer_owned' : 'third_party'),
                'started_at' => $row->started_at,
                'ended_at' => $row->ended_at,
                'is_current' => $row->is_current,
                'notes' => $row->notes,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $definitions = [
            'Customer' => ['customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customer-vehicles.view', 'customer-vehicles.create', 'customer-vehicles.update', 'customer-vehicles.set-current', 'customer-vehicles.clear-current', 'customer-vehicles.delete'],
            'Supplier' => ['suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete', 'supplier-vehicles.view', 'supplier-vehicles.create', 'supplier-vehicles.update', 'supplier-vehicles.set-current', 'supplier-vehicles.clear-current', 'supplier-vehicles.delete'],
        ];
        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach ($definitions as $module => $names) {
                foreach ($names as $name) {
                    DB::table('permissions')->updateOrInsert(['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard], ['organization_unit_id' => null, 'module' => $module, 'description' => str_replace(['-', '.'], [' ', ' '], ucfirst($name)).'.', 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
                continue;
            }
            foreach (DB::table('roles')->where('tenant_id', $tenantId)->where('name', 'Super Admin')->pluck('id') as $roleId) {
                foreach (DB::table('permissions')->where('tenant_id', $tenantId)->whereIn('name', array_merge(...array_values($definitions)))->pluck('id') as $permissionId) {
                    DB::table('role_permissions')->insertOrIgnore(['tenant_id' => $tenantId, 'organization_unit_id' => null, 'role_id' => $roleId, 'permission_id' => $permissionId, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }
};
