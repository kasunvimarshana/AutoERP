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
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('module', 50)->comment('Module name (inventory, sales, etc.)');
            $table->string('key', 100)->comment('Configuration key');
            $table->text('value')->nullable()->comment('Configuration value');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('System-level config');
            $table->boolean('is_encrypted')->default(false)->comment('Value is encrypted');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'module']);
            $table->unique(['tenant_id', 'module', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
