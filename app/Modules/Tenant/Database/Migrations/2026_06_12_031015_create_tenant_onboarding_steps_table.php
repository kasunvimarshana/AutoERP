<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_onboarding_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('step', 80);
            $table->string('owner_module', 80);
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->uuid('operation_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'step'], 'tenant_onboarding_steps_tenant_step_uk');
            $table->index(['tenant_id', 'status'], 'tenant_onboarding_steps_status_idx');
            $table->index('operation_id', 'tenant_onboarding_steps_operation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding_steps');
    }
};
