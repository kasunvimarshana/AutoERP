<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('number');
            $table->uuid('vendor_id')->index();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('tax_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('delivery_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_orders'); }
};
