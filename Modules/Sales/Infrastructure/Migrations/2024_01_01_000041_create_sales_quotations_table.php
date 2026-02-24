<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('number');
            $table->uuid('customer_id')->index();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('tax_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->date('expires_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
        });
    }
    public function down(): void { Schema::dropIfExists('sales_quotations'); }
};
