<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('sku', 100);
            $table->string('name');
            $table->enum('type', ['physical', 'service', 'digital', 'combo', 'variable']);
            $table->enum('status', ['active', 'inactive', 'discontinued', 'draft'])->default('active');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('barcode', 100)->nullable();
            $table->decimal('base_price', 15, 4)->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->unsignedBigInteger('base_uom_id')->nullable();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_lot')->default(false);
            $table->json('attributes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
