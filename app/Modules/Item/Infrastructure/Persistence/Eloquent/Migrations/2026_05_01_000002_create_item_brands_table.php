<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('parent_id')->nullable()->constrained('item_brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->comment('URL-friendly unique name indicator');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('website')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'item_brands_name_uk');
            $table->index(['tenant_id', 'parent_id'], 'item_brands_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_brands');
    }
};
