<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('operation', 120);
            $table->string('reference_hash', 64);
            $table->string('scope_hash', 64);
            $table->string('payload_hash', 64);
            $table->string('reference_value', 255)->nullable();
            $table->string('status', 40)->default('in_progress');
            $table->json('result')->nullable();
            $table->json('document_ids')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('scope_hash', 'idempotency_records_scope_hash_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'operation'], 'idempotency_records_scope_idx');
            $table->index(['operation', 'reference_hash'], 'idempotency_records_reference_idx');
            $table->index('status', 'idempotency_records_status_idx');

            $table->unique(['id', 'tenant_id'], 'idempotency_records_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'idempotency_records_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_records');
    }
};
