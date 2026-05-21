<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('attribute_id')->constrained('item_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'attribute_id', 'value'],
                'item_attribute_values_value_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_attribute_values');
    }
};
