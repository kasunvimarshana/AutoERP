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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('tax_group_id')->constrained('tax_groups')->cascadeOnDelete();
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->string('party_type')->nullable()->comment('Generic party role/type such as customer, supplier, employee, party, or external_party');
            $table->string('region')->nullable()->comment('country, state');
            $table->unsignedInteger('priority')->default(0)->comment('higher priority wins');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'tax_group_id'], 'tax_rules_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
