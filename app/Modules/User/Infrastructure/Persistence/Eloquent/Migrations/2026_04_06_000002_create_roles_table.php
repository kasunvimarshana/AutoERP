<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = (string) config('auth.defaults.guard', 'api');

        Schema::create('roles', function (Blueprint $table) use ($guardName){
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('guard_name')->default($guardName);
            $table->string('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_name_guard_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
