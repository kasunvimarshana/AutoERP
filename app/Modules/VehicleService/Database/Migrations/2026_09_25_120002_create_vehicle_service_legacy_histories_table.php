<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_legacy_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vs_legacy_histories_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('organization_scope_key', 40);
            $table->foreignId('import_batch_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('customer_id')->nullable();
            $table->string('source_system', 80);
            $table->string('source_table', 80);
            $table->string('source_record_id', 100);
            $table->char('source_payload_sha256', 64);
            $table->string('legacy_job_number', 100);
            $table->date('service_date');
            $table->string('job_type', 30)->nullable();
            $table->string('job_type_raw', 100);
            $table->string('status_raw', 100)->nullable();
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->string('odometer_unit', 30)->nullable();
            $table->decimal('next_service_mileage', 20, 6)->nullable();
            $table->string('vehicle_registration_snapshot')->nullable();
            $table->string('customer_code_snapshot', 100)->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->json('source_payload');
            $table->timestamp('imported_at');

            $table->unique(['id', 'tenant_id'], 'vs_legacy_histories_id_tenant_uk');
            $table->unique(['tenant_id', 'organization_scope_key', 'source_system', 'source_table', 'source_record_id'], 'vs_legacy_histories_source_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id', 'service_date'], 'vs_legacy_histories_vehicle_date_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'job_type', 'service_date'], 'vs_legacy_histories_type_date_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vs_legacy_histories_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['import_batch_id', 'tenant_id'], 'vs_legacy_histories_batch_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_legacy_import_batches')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vs_legacy_histories_vehicle_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'vs_legacy_histories_customer_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_legacy_histories');
    }
};
