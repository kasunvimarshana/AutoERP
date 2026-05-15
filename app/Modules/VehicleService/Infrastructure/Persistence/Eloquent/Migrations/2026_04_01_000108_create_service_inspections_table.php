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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->string('inspection_number');
            $table->string('inspection_phase')->default('pre');
            $table->string('inspection_type');
            $table->string('overall_result')->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'inspection_number'], 'service_inspections_inspection_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'job_card_id'], 'service_inspections_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inspections');
    }
};
