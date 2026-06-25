<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id');
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'customer_documents_tenant_org_idx');
            $table->index('customer_id', 'customer_documents_customer_idx');
            $table->index('document_type', 'customer_documents_type_idx');
            $table->index('expiry_date', 'customer_documents_expiry_idx');
            $table->index('status', 'customer_documents_status_idx');

            $table->unique(['id', 'tenant_id'], 'customer_documents_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customer_documents_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'customer_documents_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
