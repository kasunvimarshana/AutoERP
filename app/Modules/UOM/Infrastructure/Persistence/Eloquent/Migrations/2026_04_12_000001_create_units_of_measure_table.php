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

            $table->string('name');
            $table->string('symbol');
            $table->string('type')->default('UNIT')->comment('UNIT, MASS, VOLUME, LENGTH, TIME, OTHER');
            $table->boolean('is_base')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'unit_of_measures_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
