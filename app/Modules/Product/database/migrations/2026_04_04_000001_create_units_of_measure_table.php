<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('code', 30);
            $table->string('name', 100);
            // weight, volume, length, area, time, piece, custom
            $table->string('type', 30)->default('piece');
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('conversion_factor', 15, 6)->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
