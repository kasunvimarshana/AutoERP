<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('code', 50);
            $table->string('name');
            $table->string('symbol');
            $table->string('category')->default('UNIT')->comment('Generic UOM category such as UNIT, MASS, VOLUME, LENGTH, TIME, DISTANCE, OTHER');
            $table->string('type')->default('UNIT')->comment('UNIT, MASS, VOLUME, LENGTH, TIME, DISTANCE, OTHER');
            $table->unsignedTinyInteger('decimal_precision')->default(0);
            $table->boolean('allow_fractional_quantity')->default(false);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'unit_of_measures_code_uk');
            $table->unique(['tenant_id', 'name'], 'unit_of_measures_name_uk');
            $table->index(['tenant_id', 'type', 'is_active'], 'unit_of_measures_type_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
