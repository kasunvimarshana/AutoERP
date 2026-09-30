<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_whatsapp_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'supplier_wa_verifications_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('supplier_contact_id')->nullable();
            $table->string('normalized_phone', 15);
            $table->string('phone_source', 50);
            $table->uuid('idempotency_key');
            $table->string('code_hash');
            $table->string('status', 30);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('max_attempts');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key'], 'supplier_wa_verifications_idempotency_uk');
            $table->unique(['id', 'tenant_id'], 'supplier_wa_verifications_id_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_wa_verifications_tenant_org_ix');
            $table->index(['supplier_id', 'normalized_phone', 'status'], 'supplier_wa_verifications_phone_status_ix');
            $table->index(['supplier_id', 'status', 'expires_at'], 'supplier_wa_verifications_pending_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_wa_verifications_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_wa_verifications_supplier_tenant_fk')
                ->references(['id', 'tenant_id'])->on('suppliers')->restrictOnDelete();
            $table->foreign(['supplier_contact_id', 'tenant_id'], 'supplier_wa_verifications_contact_tenant_fk')
                ->references(['id', 'tenant_id'])->on('supplier_contacts')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'supplier_wa_verifications_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['verified_by', 'tenant_id'], 'supplier_wa_verifications_verified_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_whatsapp_verifications');
    }
};
