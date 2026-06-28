<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'attachments_tenant_fk')->restrictOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('attachable_type')->comment('polymorphic target type (e.g., Document, Product, Party)');
            $table->unsignedBigInteger('attachable_id')->comment('polymorphic target ID');
            $table->string('source_module')->nullable()->comment('Generic source module key');
            $table->string('source_type')->nullable()->comment('Generic source record type');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Generic source identifier');
            $table->string('source_reference')->nullable()->comment('Human-readable source number/reference');
            $table->json('source_context')->nullable()->comment('Additional source context supplied by owning module');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable()->comment('File size in bytes');

            $table->timestamps();

            $table->index(['tenant_id', 'attachable_type', 'attachable_id'], 'attachments_type_id_ix');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'attachments_source_ix');

            $table->unique(['id', 'tenant_id'], 'attachments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'attachments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
