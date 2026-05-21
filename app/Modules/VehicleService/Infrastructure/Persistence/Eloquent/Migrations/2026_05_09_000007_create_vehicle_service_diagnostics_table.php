<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            $table->string('diagnostic_number');
            $table->string('diagnostic_phase')->nullable()->comment('pre, post');
            $table->string('diagnostic_type')->comment('engine, transmission, electrical, etc.');
            $table->string('overall_result')->default('pass')->comment('pass, fail, warning, not_applicable');
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'diagnostic_number'], 'vehicle_service_diagnostics_diagnostic_number_uk');
            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_diagnostics_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_diagnostics');
    }
};
