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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tax_category_id')->constrained('tax_categories')->cascadeOnDelete();
            $table->foreignId('country_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->decimal('rate', 9, 6);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'tax_category_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
