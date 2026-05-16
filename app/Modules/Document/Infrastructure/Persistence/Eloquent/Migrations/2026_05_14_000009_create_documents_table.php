<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('document_number');
            $table->string('status');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->json('data')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'document_number'], 'documents_tenant_number_unique');
            $table->index(['tenant_id', 'document_type_id', 'status'], 'documents_tenant_type_status_index');
            $table->index(['tenant_id', 'document_date'], 'documents_tenant_date_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX documents_data_gin ON documents USING GIN (data)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
