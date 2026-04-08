<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('code', 50);
            $table->string('name', 200);
            $table->string('type', 30)->default('company'); // individual, company
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('credit_limit', 15, 4)->default(0);
            $table->decimal('balance', 15, 4)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->string('status', 30)->default('active'); // active, inactive
            $table->string('billing_address_line1', 255)->nullable();
            $table->string('billing_address_line2', 255)->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            $table->string('billing_country', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
