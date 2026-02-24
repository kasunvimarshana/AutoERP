<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('active');
            $table->decimal('rating', 3, 2)->nullable();
            $table->json('bank_details')->nullable();
            $table->json('address')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_vendors'); }
};
