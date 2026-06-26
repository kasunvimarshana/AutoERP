<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('name');
            $table->string('guard_name', 100);
            $table->string('module', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'permissions_id_tenant_uk');
            $table->unique(['tenant_id', 'name', 'guard_name'], 'permissions_name_guard_uk');
            $table->index(['tenant_id', 'module', 'is_active'], 'permissions_module_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
