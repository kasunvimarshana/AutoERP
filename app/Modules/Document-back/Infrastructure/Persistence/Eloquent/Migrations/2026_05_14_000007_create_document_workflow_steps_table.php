<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('document_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('name');
            $table->string('display_name');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();

            $table->unique(['workflow_id', 'name'], 'document_workflow_steps_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_steps');
    }
};
