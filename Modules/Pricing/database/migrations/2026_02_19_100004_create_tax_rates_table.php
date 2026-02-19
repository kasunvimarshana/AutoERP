<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->decimal('rate', 8, 4);
            $table->string('jurisdiction')->nullable();
            $table->string('product_category')->nullable();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'code']);
            $table->index(['is_active', 'priority']);
            $table->index('jurisdiction');
            $table->index('product_category');
            $table->index(['effective_date', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
