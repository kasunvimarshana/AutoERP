<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_job_line_choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_job_choices_tenant_fk')->restrictOnDelete();
            $table->foreignId('job_line_id');
            $table->foreignId('card_id')->nullable();
            $table->foreignId('card_benefit_id')->nullable();
            $table->foreignId('offer_assignment_id')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('selected_by')->nullable();
            $table->timestamps();

            $table->unique('job_line_id', 'promotion_job_choices_line_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_job_choices_id_tenant_uk');
            $table->foreign(['job_line_id', 'tenant_id'], 'promotion_job_choices_line_fk')->references(['id', 'tenant_id'])->on('vehicle_service_job_lines')->restrictOnDelete();
            $table->foreign(['card_id', 'tenant_id'], 'promotion_job_choices_card_fk')->references(['id', 'tenant_id'])->on('promotion_cards')->restrictOnDelete();
            $table->foreign(['card_benefit_id', 'tenant_id'], 'promotion_job_choices_benefit_fk')->references(['id', 'tenant_id'])->on('promotion_card_benefits')->restrictOnDelete();
            $table->foreign(['offer_assignment_id', 'tenant_id'], 'promotion_job_choices_assignment_fk')->references(['id', 'tenant_id'])->on('promotion_offer_assignments')->restrictOnDelete();
            $table->foreign(['selected_by', 'tenant_id'], 'promotion_job_choices_user_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_job_line_choices');
    }
};
