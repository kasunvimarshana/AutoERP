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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete()->comment('Parent category for hierarchical structure');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete()->comment('Branch isolation');
            $table->string('name');
            $table->string('code')->comment('Unique category code');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per branch
            $table->unique(['branch_id', 'code']);

            // Indexes for performance
            $table->index('name');
            $table->index('code');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
