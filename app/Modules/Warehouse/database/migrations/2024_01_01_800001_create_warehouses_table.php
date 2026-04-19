<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'warehouses_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id', 'warehouses_org_unit_id_fk')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('type', ['standard', 'virtual', 'transit', 'quarantine'])->default('standard');
            $table->foreignId('address_id')->nullable(); // can reference a polymorphic address later
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'warehouses_tenant_code_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
