<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'org_unit_types_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'org_unit_types_tenant_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_types');
    }
};
