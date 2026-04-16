<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 7, 4);
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            // Tax rates account
            $table->foreignId('account_id')->nullable(); // tax liability/payable account
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->enum('party_type', ['customer', 'supplier'])->nullable();
            $table->string('region')->nullable(); // e.g., state, country code
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_classes');
    }
};