<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 64)->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('sku', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('attributes')->nullable();
            $table->jsonb('images')->nullable();
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->string('unit_of_measure', 50)->default('unit');
            $table->decimal('weight', 10, 4)->nullable();
            $table->jsonb('dimensions')->nullable();
            $table->string('barcode', 100)->nullable()->index();
            $table->integer('minimum_stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku']);
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'reorder_point']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
