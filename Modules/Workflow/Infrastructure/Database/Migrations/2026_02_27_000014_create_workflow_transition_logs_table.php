<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transition_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workflow_instance_id');
            $table->unsignedBigInteger('from_state_id')->nullable();
            $table->unsignedBigInteger('to_state_id');
            $table->string('event_name');
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('transitioned_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->onDelete('cascade');
            $table->foreign('from_state_id')->references('id')->on('workflow_states')->onDelete('restrict');
            $table->foreign('to_state_id')->references('id')->on('workflow_states')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transition_logs');
    }
};
