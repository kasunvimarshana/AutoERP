<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('activity_type')->comment('call/email/meeting/task/note');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();

            $table->foreign('opportunity_id')->references('id')->on('crm_opportunities')->nullOnDelete();
            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
