<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->constrained('organization_units', 'id')->cascadeOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('user_id')->constrained('users', 'id', 'org_unit_users_user_id_fk')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles', 'id', 'org_unit_users_role_id_fk')->nullOnDelete();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'user_id'], 'organization_unit_users_user_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'user_id'], 'organization_unit_users_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_users');
    }
};
