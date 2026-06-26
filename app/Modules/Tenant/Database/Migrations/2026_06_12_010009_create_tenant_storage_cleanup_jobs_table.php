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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'tenant_storage_cleanup_jobs_tenant_fk')->restrictOnDelete();
            $table->string('storage_path');
            $table->string('reason', 255);
            $table->enum('status', ['pending', 'processing', 'completed', 'dead'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->string('last_error_message', 255)->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_lease_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'storage_path'], 'tenant_storage_cleanup_path_uk');
            $table->index(['status', 'next_attempt_at'], 'tenant_storage_cleanup_due_ix');
            $table->index(['status', 'claimed_at'], 'tenant_storage_cleanup_claim_ix');
            $table->index(['tenant_id', 'status'], 'tenant_storage_cleanup_tenant_ix');
            $table->unique(['id', 'tenant_id'], 'tenant_storage_cleanup_jobs_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_storage_cleanup_jobs');
    }
};
