<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('parent_id')->nullable()->constrained('product_categories', 'id', 'product_categories_parent_id_fk')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'code'], 'product_categories_tenant_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'product_categories_tenant_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
