<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('user_id')->constrained('users', 'id', 'org_unit_users_user_id_fk')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'user_id'], 'org_unit_users_tenant_org_user_uk');
            $table->index(['tenant_id', 'user_id'], 'org_unit_users_tenant_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_users');
    }
};
