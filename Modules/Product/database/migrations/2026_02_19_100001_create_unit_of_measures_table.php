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
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete()->comment('Branch isolation');
            $table->string('name')->comment('Unit name (e.g., Kilogram, Liter)');
            $table->string('code')->comment('Short code (e.g., kg, L)');
            $table->string('type')->comment('Unit type (weight, volume, length, quantity, etc.)');
            $table->boolean('is_base_unit')->default(false)->comment('Is this the base unit for its type?');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per branch
            $table->unique(['branch_id', 'code']);

            // Indexes
            $table->index('name');
            $table->index('code');
            $table->index('type');
            $table->index('is_base_unit');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
