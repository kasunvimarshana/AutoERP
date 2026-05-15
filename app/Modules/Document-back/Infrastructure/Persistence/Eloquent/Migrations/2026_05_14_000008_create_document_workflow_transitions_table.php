<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflow_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_step_id')->constrained('document_workflow_steps')->cascadeOnDelete();
            $table->foreignId('to_step_id')->constrained('document_workflow_steps')->cascadeOnDelete();
            $table->string('action_name');
            $table->text('condition_expression')->nullable();
            $table->string('required_ability')->nullable();
            $table->timestamps();

            $table->unique(['from_step_id', 'to_step_id', 'action_name'], 'document_workflow_transitions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_transitions');
    }
};
