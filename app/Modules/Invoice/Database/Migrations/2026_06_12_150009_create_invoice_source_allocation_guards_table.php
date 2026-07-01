<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_allocation_guards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'invoice_source_allocation_guards_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_line_type', 100);
            $table->unsignedBigInteger('source_line_id');
            $table->char('allocation_key', 64);
            $table->timestamps();

            $table->unique('allocation_key', 'invoice_source_allocation_guards_key_uk');
            $table->index(
                ['tenant_id', 'organization_unit_id', 'source_type', 'source_id'],
                'invoice_source_allocation_guards_source_ix',
            );
            $table->unique(['id', 'tenant_id'], 'invoice_source_allocation_guards_id_tenant_uk');
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'invoice_source_allocation_guards_org_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_allocation_guards');
    }
};
