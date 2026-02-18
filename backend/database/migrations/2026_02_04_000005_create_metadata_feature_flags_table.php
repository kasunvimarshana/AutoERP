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
        Schema::create('metadata_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name'); // Feature flag key
            $table->string('label'); // Display label
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('module')->nullable(); // Related module
            $table->json('config')->nullable(); // Feature-specific configuration
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index(['is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_feature_flags');
    }
};
