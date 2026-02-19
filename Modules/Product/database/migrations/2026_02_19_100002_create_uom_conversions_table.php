<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_uom_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->decimal('conversion_factor', 20, 10)->comment('Multiply by this to convert from_uom to to_uom');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Ensure unique conversion pairs
            $table->unique(['from_uom_id', 'to_uom_id']);

            // Indexes
            $table->index('from_uom_id');
            $table->index('to_uom_id');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
