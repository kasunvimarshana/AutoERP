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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('from_uom_id')->constrained('units_of_measure', 'id', 'uom_conversions_from_uom_id_fk')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('units_of_measure', 'id', 'uom_conversions_to_uom_id_fk')->cascadeOnDelete();
            $table->decimal('factor', 20, 10);
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products', 'id', 'uom_conversions_product_id_fk')
                ->cascadeOnDelete();
            $table->boolean('is_bidirectional')->default(true)->after('factor');
            $table->boolean('is_active')->default(true)->after('is_bidirectional');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'product_id', 'from_uom_id', 'to_uom_id'], 'uom_conversions_scope_from_to_uk');
            $table->index(['tenant_id', 'product_id', 'is_active'], 'uom_conversions_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
