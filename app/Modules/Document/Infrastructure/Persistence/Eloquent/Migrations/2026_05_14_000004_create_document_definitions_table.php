<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type_id', 'version'], 'document_definitions_unique');
            $table->index(['tenant_id', 'is_active'], 'document_definitions_tenant_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definitions');
    }
};
