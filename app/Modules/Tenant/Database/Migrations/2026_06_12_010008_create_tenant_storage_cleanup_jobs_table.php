<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_storage_cleanup_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('storage_disk', 100);
            $table->string('storage_path');
            $table->string('reason', 255);
            $table->enum('status', ['pending', 'processing', 'completed', 'dead'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['storage_disk', 'storage_path'], 'tenant_storage_cleanup_path_uk');
            $table->index(['status', 'next_attempt_at'], 'tenant_storage_cleanup_due_idx');
            $table->index(['status', 'claimed_at'], 'tenant_storage_cleanup_claim_idx');
            $table->index(['tenant_id', 'status'], 'tenant_storage_cleanup_tenant_idx');

            $table->unique(['id', 'tenant_id'], 'tenant_storage_cleanup_jobs_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_storage_cleanup_jobs');
    }
};
