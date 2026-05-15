<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('target_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('link_type')->default('reference')->comment('reference, return, credit, conversion');

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'source_document_id', 'target_document_id', 'link_type'], 'document_links_source_target_type_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
