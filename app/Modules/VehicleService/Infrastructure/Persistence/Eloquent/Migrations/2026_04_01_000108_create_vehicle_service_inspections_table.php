<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('inspection_number');
            $table->string('inspection_phase')->nullable()->comment('pre, post');
            $table->string('inspection_type')->comment('safety, emissions, pre_purchase, periodic, etc.');
            $table->string('overall_result')->default('pass')->comment('pass, fail, warning, not_applicable');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'inspection_number'], 'vehicle_service_inspections_inspection_number_uk');
            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_inspections_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_inspections');
    }
};
