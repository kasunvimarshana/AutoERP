<?php

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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('symbol', 20)->nullable();
            $table->enum('type', ['basic', 'mass', 'length', 'volume', 'area', 'time', 'temperature', 'quantity'])->default('quantity');
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->decimal('conversion_factor', 20, 8)->default(1.0)->comment('Factor to convert to base unit');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('System-defined unit');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
