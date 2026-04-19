<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'permissions_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('guard_name')->default('api');
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'guard_name'], 'permissions_tenant_name_guard_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
