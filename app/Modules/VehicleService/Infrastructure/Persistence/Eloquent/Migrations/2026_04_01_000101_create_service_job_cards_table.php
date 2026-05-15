<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('job_card_number')->unique('svc_jc_number_uk');
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->text('reported_issue')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('diagnostic_notes')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('completed_datetime')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->string('source')->default('walk_in');
            $table->boolean('warranty_eligible')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'service_job_cards_status_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'party_id'], 'service_job_cards_party_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'service_job_cards_vehicle_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'assigned_to'], 'service_job_cards_tech_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_cards');
    }
};
