<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();

            $table->string('inspection_number');
            $table->string('inspection_phase')->nullable()->comment('pre, post');
            $table->string('inspection_type')->comment('safety, emissions, pre_purchase, periodic, etc.');
            $table->string('overall_result')->default('pass')->comment('pass, fail, warning, not_applicable');
            $table->text('notes')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'inspection_number'], 'service_inspections_number_uk');
            $table->index(['tenant_id', 'vehicle_id'], 'inspection_vehicle_idx');
            $table->index(['tenant_id', 'job_card_id'], 'inspection_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inspections');
    }
};
