<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('from_uom_id')->constrained('unit_of_measures');
            $table->foreignId('to_uom_id')->constrained('unit_of_measures');
            $table->decimal('factor', 20, 8);
            $table->string('category')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->boolean('is_bidirectional')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'item_id', 'from_uom_id', 'to_uom_id'],
                'uom_conversions_item_from_to_uk'
            );
            $table->index(['tenant_id', 'item_id', 'is_active'], 'uom_conversions_item_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
