<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'));

        Schema::create('roles', function (Blueprint $table) use ($guardName) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->string('guard_name')->default($guardName);
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'name', 'guard_name'], 'roles_tenant_name_guard_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
