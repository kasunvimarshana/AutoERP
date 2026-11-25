<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_tax_brackets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'payroll_tax_brackets_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->unsignedBigInteger('tax_table_id');
            $table->unsignedSmallInteger('bracket_order');
            $table->decimal('threshold_from', 20, 6);
            $table->decimal('threshold_to', 20, 6)->nullable(); // null = unlimited
            $table->decimal('rate_percentage', 5, 2);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'payroll_tax_brackets_id_tenant_uk');
            $table->unique(['tax_table_id', 'bracket_order'], 'payroll_tax_brackets_order_uk');
            $table->index(['tenant_id', 'tax_table_id'], 'payroll_tax_brackets_table_ix');

            $table->foreign(['tax_table_id', 'tenant_id'], 'payroll_tax_brackets_table_fk')
                ->references(['id', 'tenant_id'])
                ->on('payroll_tax_tables')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'payroll_tax_brackets_org_unit_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_brackets');
    }
};
