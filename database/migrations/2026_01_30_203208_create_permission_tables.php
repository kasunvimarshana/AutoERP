<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // Create permissions table
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->index(['tenant_id', 'guard_name']);
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        // Create roles table
        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->index(['tenant_id', 'guard_name']);
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        // Create model_has_permissions table
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->foreignId('permission_id')->constrained($tableNames['permissions'])->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type', 'tenant_id'],
                'model_has_permissions_permission_model_type_primary');

            $table->index([$columnNames['model_morph_key'], 'model_type', 'tenant_id'], 'model_has_permissions_model_id_model_type_index');
        });

        // Create model_has_roles table
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->foreignId('role_id')->constrained($tableNames['roles'])->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type', 'tenant_id'],
                'model_has_roles_role_model_type_primary');

            $table->index([$columnNames['model_morph_key'], 'model_type', 'tenant_id'], 'model_has_roles_model_id_model_type_index');
        });

        // Create role_has_permissions table
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->foreignId('permission_id')->constrained($tableNames['permissions'])->onDelete('cascade');
            $table->foreignId('role_id')->constrained($tableNames['roles'])->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->primary(['permission_id', 'role_id', 'tenant_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged.');
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
