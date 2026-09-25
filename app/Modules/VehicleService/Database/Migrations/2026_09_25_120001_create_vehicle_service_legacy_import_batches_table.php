<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_legacy_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vs_legacy_batches_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('organization_scope_key', 40);
            $table->string('source_system', 80);
            $table->string('source_filename');
            $table->char('source_sha256', 64);
            $table->string('status', 30);
            $table->unsignedInteger('source_job_count')->default(0);
            $table->unsignedInteger('imported_job_count')->default(0);
            $table->unsignedInteger('imported_line_count')->default(0);
            $table->unsignedInteger('created_customer_count')->default(0);
            $table->unsignedInteger('created_vehicle_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vs_legacy_batches_id_tenant_uk');
            $table->unique(['tenant_id', 'organization_scope_key', 'source_system', 'source_sha256'], 'vs_legacy_batches_source_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'vs_legacy_batches_scope_status_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vs_legacy_batches_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_legacy_import_batches');
    }
};
