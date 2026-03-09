<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 64)->index();
            $table->uuid('product_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->string('type', 50);
            $table->decimal('quantity', 15, 4);
            $table->decimal('before_quantity', 15, 4)->default(0);
            $table->decimal('after_quantity',  15, 4)->default(0);
            $table->string('reference_id',   255)->nullable()->index();
            $table->string('reference_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('performed_by', 255)->nullable();
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->index(['tenant_id', 'type']);
            $table->index(['reference_id', 'reference_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
