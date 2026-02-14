<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->enum('type', ['percentage', 'fixed', 'compound'])->default('percentage');
            $table->decimal('rate', 10, 4)->comment('Tax rate (percentage or fixed amount)');
            $table->boolean('is_inclusive')->default(false)->comment('Tax included in price');
            $table->boolean('is_compound')->default(false)->comment('Compound tax (applies after other taxes)');
            $table->integer('priority')->default(0)->comment('Order of tax calculation');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
