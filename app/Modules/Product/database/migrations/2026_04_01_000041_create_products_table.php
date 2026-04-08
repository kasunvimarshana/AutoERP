<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('name');
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->enum('type', ['physical','service','digital','combo','variable'])->default('physical');
            $table->enum('status', ['draft','active','inactive','discontinued'])->default('draft');
            $table->text('description')->nullable();
            $table->string('short_description')->nullable();
            $table->string('unit')->default('each');
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_trackable')->default(true);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('sale_price', 15, 4)->default(0);
            $table->decimal('min_stock_level', 15, 4)->default(0);
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->uuid('tax_group_id')->nullable();
            $table->string('image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
