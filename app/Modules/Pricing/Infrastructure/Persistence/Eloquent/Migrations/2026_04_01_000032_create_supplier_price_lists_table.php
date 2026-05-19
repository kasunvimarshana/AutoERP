<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained('price_lists', 'id')->cascadeOnDelete();
            $table->unsignedInteger('priority')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_id', 'price_list_id'], 'supplier_price_lists_unique_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_price_lists');
    }
};
