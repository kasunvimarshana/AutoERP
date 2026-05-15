<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name');
            $table->string('rule_type')->default('daily_rate');
            $table->foreignId('vehicle_category_id')->nullable();
            $table->decimal('base_rate', 20, 4);
            $table->decimal('min_rate', 20, 4)->nullable();
            $table->decimal('max_rate', 20, 4)->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'rule_type', 'is_active'], 'rental_pricing_rules_rule_type_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_pricing_rules');
    }
};
