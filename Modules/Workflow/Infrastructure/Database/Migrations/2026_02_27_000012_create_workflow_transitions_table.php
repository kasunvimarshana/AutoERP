<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workflow_definition_id');
            $table->unsignedBigInteger('from_state_id');
            $table->unsignedBigInteger('to_state_id');
            $table->string('event_name');
            $table->string('guard_class')->nullable();
            $table->string('action_class')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->onDelete('cascade');
            $table->foreign('from_state_id')->references('id')->on('workflow_states')->onDelete('cascade');
            $table->foreign('to_state_id')->references('id')->on('workflow_states')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
