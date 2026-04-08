<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('from_uom_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->decimal('multiplier', 24, 8);
            $table->decimal('divisor', 24, 8)->default(1);
            $table->string('conversion_scope')->default("global");
            $table->unique(['tenant_id', 'from_uom_id', 'to_uom_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
