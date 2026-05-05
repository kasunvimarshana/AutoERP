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
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'supplier_price_lists_tenant_id_fk')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained(null, 'id', 'supplier_price_lists_supplier_id_fk')->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained(null, 'id', 'supplier_price_lists_price_list_id_fk')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'supplier_id', 'price_list_id'], 'supplier_price_lists_tenant_supplier_pricelist_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_price_lists');
    }
};
