<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sale_return_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sale_return_id');
            $table->foreignId('sale_line_id');
            $table->decimal('quantity', 20, 6);
            $table->decimal('credit_amount', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable();
            $table->timestamps();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'sale_return_lines_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['sale_return_id', 'tenant_id'], 'sale_return_lines_return_tenant_fk')
                ->references(['id', 'tenant_id'])->on('sale_returns')->restrictOnDelete();
            $table->foreign(['sale_line_id', 'tenant_id'], 'sale_return_lines_sale_line_tenant_fk')
                ->references(['id', 'tenant_id'])->on('sale_lines')->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'sale_return_lines_movement_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_movements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_lines');
    }
};
