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
        Schema::create('metadata_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('metadata_menus')->onDelete('cascade');
            $table->string('name'); // Internal name
            $table->string('label'); // Display label
            $table->string('icon')->nullable(); // Icon class/name
            $table->string('route')->nullable(); // Frontend route path
            $table->string('url')->nullable(); // External URL
            $table->string('entity_name')->nullable(); // Related entity
            $table->string('permission')->nullable(); // Required permission
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->json('config')->nullable(); // Additional configuration
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'parent_id', 'order']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_menus');
    }
};
