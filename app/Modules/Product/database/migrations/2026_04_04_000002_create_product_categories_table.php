<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'parent_id']);

            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
