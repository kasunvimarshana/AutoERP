<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tax_group_id')->constrained('tax_groups', 'id', 'tax_rules_tax_group_id_fk')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories', 'id', 'tax_rules_product_category_id_fk')->nullOnDelete();
            $table->enum('party_type', ['customer', 'supplier'])->nullable();
            $table->string('region')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
