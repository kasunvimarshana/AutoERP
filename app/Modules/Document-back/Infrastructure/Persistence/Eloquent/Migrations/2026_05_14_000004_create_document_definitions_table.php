<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->json('header_schema');
            $table->json('allowed_item_types')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('form_layout')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type_id', 'version'], 'document_definitions_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX document_definitions_header_schema_gin ON document_definitions USING GIN (header_schema)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definitions');
    }
};
