<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->integer('sequence');
            $table->json('config')->nullable();
            $table->json('action_config')->nullable();
            $table->json('approval_config')->nullable();
            $table->json('condition_config')->nullable();
            $table->integer('timeout_seconds')->nullable();
            $table->integer('retry_count')->default(0);
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workflow_id', 'sequence']);
            $table->index(['workflow_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
