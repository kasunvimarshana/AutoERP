<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_domains', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->string('domain', 253)->unique('tenant_domains_domain_uk');
            $table->enum('status', ['pending', 'active', 'disabled'])->default('pending');
            $table->enum('verification_method', ['dns_txt'])->default('dns_txt');
            $table->char('verification_token_hash', 64)->nullable()->comment('Pending DNS verification challenge hash');
            $table->char('verified_token_hash', 64)->nullable()->comment('Last successfully verified DNS ownership proof hash');
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_verification_attempt_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('verification_failure_count')->default(0);
            $table->string('verification_last_error', 500)->nullable();
            $table->timestamp('revalidation_due_at')->nullable();
            $table->timestamp('verification_grace_expires_at')->nullable();
            $table->uuid('revalidation_claim_token')->nullable();
            $table->timestamp('revalidation_claimed_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()->index('tenant_domains_verified_by_idx');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_domains_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_domains_updated_by_idx');
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'tenant_domains_tenant_status_idx');
            $table->index(
                ['status', 'revalidation_due_at', 'revalidation_claimed_at'],
                'tenant_domains_revalidation_idx',
            );
            $table->unique(['id', 'tenant_id'], 'tenant_domains_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
