<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->decimal('rate', 8, 4);
            $table->boolean('is_tax_group')->default(false);
            $table->uuid('tax_group_id')->nullable(); // if this is a sub-tax
            $table->string('calculation_type')->default('exclusive'); // inclusive, exclusive
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('tax_group_id')->references('id')->on('pos_tax_rates')->onDelete('set null');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_tax_rates');
    }
};
