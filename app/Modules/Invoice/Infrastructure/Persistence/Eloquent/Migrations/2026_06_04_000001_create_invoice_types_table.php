<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');

            $table->string('code', 100);
            $table->string('name');
            $table->string('module_key', 120)->nullable()->comment('Owning/default module key; nullable for shared invoice types');
            $table->string('direction')->comment('payable, receivable, internal');
            $table->unsignedInteger('schema_version')->default(1);
            $table->string('number_sequence_key', 120)->nullable();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types', 'id')->nullOnDelete();
            $table->string('default_status')->default('draft')->comment('draft, approved, posted, partially_paid, paid, cancelled, reversed');
            $table->json('settings_json')->nullable()->comment('Invoice type settings and module-specific rendering hints');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'invoice_types_tenant_code_uk');
            $table->index(['tenant_id', 'module_key', 'direction', 'is_active'], 'invoice_types_module_direction_idx');
            $table->index(['tenant_id', 'document_type_id'], 'invoice_types_document_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_types');
    }
};
