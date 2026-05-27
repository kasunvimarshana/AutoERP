<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('principal_type');
            $table->string('principal_identifier');
            $table->string('ability');
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_permissions_tenant_document_index');
            $table->unique(
                ['tenant_id', 'document_id', 'principal_type', 'principal_identifier', 'ability'],
                'document_permissions_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
