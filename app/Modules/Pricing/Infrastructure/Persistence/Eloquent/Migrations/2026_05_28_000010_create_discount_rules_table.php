<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();

            $table->unsignedInteger('sequence')->default(1);
            $table->string('logical_operator')->default('and')->comment('and, or');
            $table->string('condition_type')->default('field')->comment('field, relation, expression');
            $table->string('field');
            $table->string('operator');
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'discount_id', 'sequence'], 'discount_rules_discount_sequence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
