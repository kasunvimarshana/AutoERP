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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('tax_group_id')->constrained('tax_groups')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('party_type')->nullable()->comment('customer, supplier');
            $table->string('region')->nullable()->comment('country, state');
            $table->unsignedInteger('priority')->default(0)->comment('higher priority wins');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'tax_group_id'], 'tax_rules_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
