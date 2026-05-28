<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('entity_type')->comment('job_card, job_card_line, labor_item, labor_assignment');
            $table->unsignedBigInteger('entity_id');
            $table->string('workflow_action')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');

            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'vehicle_service_status_hist_entity_idx');
            $table->index(['tenant_id', 'changed_at'], 'vehicle_service_status_hist_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_status_histories');
    }
};
