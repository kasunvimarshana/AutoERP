<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('permission_id')->constrained(null, 'id', 'permission_role_permission_id_fk')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained(null, 'id', 'permission_role_role_id_fk')->cascadeOnDelete();
            $table->primary(['tenant_id', 'permission_id', 'role_id'], 'permission_role_tenant_permission_role_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
