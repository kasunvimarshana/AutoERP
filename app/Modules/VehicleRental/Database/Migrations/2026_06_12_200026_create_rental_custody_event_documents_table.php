<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_custody_event_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'rental_custody_docs_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('custody_event_id');
            $table->foreignId('private_object_id');
            $table->string('document_type', 60)->default('evidence');
            $table->string('description', 500)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id','custody_event_id','private_object_id'], 'rental_custody_docs_object_uk');
            $table->foreign(['organization_unit_id','tenant_id'], 'rental_custody_docs_ou_tenant_fk')->references(['id','tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['custody_event_id','tenant_id'], 'rental_custody_docs_event_tenant_fk')->references(['id','tenant_id'])->on('rental_custody_events')->restrictOnDelete();
            $table->foreign(['private_object_id','tenant_id'], 'rental_custody_docs_object_tenant_fk')->references(['id','tenant_id'])->on('private_objects')->restrictOnDelete();
            $table->foreign(['created_by','tenant_id'], 'rental_custody_docs_creator_tenant_fk')->references(['id','tenant_id'])->on('users')->restrictOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('rental_custody_event_documents'); }
};
