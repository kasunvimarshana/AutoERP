<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('module');
            $table->string('document_type');
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedInteger('padding')->default(5);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'module', 'document_type'], 'numbering_sequences_tenant_module_doc_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
