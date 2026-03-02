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
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete();
            $table->foreignId('from_state_id')
                ->constrained('workflow_states')
                ->cascadeOnDelete();
            $table->foreignId('to_state_id')
                ->constrained('workflow_states')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('requires_comment')->default(false);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'from_state_id', 'to_state_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
