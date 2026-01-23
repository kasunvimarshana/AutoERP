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
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('package_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('package_type', ['routine_service', 'seasonal', 'promotional', 'custom'])->default('routine_service');
            $table->decimal('regular_price', 10, 2);
            $table->decimal('package_price', 10, 2);
            $table->decimal('savings', 10, 2)->nullable();
            $table->integer('validity_days')->nullable(); // How long the package is valid
            $table->json('included_items')->nullable(); // List of services/parts included
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index('package_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
