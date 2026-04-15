<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('type', ['customer', 'supplier', 'employee', 'partner', 'other']);
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('credit_limit', 18, 4)->nullable();
            $table->integer('payment_terms_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();

            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};