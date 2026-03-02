<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_states');
    }
};
