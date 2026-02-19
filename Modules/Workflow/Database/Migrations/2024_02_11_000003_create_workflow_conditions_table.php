<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_id')->constrained('workflow_steps')->onDelete('cascade');
            $table->string('type', 50);
            $table->string('field');
            $table->string('operator', 50);
            $table->json('value')->nullable();
            $table->foreignId('next_step_id')->nullable()->constrained('workflow_steps')->onDelete('set null');
            $table->boolean('is_default')->default(false);
            $table->integer('sequence');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['step_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_conditions');
    }
};
