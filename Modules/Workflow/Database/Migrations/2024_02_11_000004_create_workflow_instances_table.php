<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->ulid('tenant_id');
            $table->ulid('organization_id')->nullable();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('status', 50);
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->onDelete('set null');
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_id']);
            $table->index(['workflow_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('started_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
