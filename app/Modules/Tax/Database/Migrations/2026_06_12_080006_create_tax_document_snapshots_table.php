<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_document_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('source_module', 100)->nullable();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_number', 150)->nullable();
            $table->date('source_date')->nullable();
            $table->string('line_type', 100)->nullable();
            $table->unsignedBigInteger('line_id')->nullable();
            $table->foreignId('tax_id')->nullable();
            $table->string('tax_code', 100);
            $table->string('tax_name');
            $table->string('tax_type', 100);
            $table->string('calculation_method', 30);
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->unsignedInteger('sequence')->default(1);
            $table->decimal('taxable_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');
            $table->boolean('is_withholding')->default(false);
            $table->boolean('recoverable')->default(false);
            $table->boolean('payable')->default(false);
            $table->boolean('receivable')->default(false);
            $table->boolean('posted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'source_type', 'source_id'], 'tax_snapshots_source_idx');
            $table->index(['tax_code', 'source_date'], 'tax_snapshots_code_date_idx');

            $table->unique(['id', 'tenant_id'], 'tax_document_snapshots_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'tax_document_snapshots_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['tax_id', 'tenant_id'], 'tax_document_snapshots_tax_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('taxes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_document_snapshots');
    }
};
