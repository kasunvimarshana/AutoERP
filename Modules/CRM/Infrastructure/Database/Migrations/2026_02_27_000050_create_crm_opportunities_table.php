<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('pipeline_stage_id');
            $table->string('title');
            $table->decimal('expected_revenue', 20, 4)->default(0);
            $table->date('close_date')->nullable();
            $table->string('status')->default('open')->comment('open/won/lost');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->decimal('probability', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
            $table->foreign('pipeline_stage_id')->references('id')->on('crm_pipeline_stages')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_opportunities');
    }
};
