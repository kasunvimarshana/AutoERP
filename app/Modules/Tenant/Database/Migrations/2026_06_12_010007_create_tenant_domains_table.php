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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'tenant_domains_tenant_fk')->restrictOnDelete();
            $table->string('domain', 253)->unique('tenant_domains_domain_uk');
            $table->enum('status', ['pending', 'active', 'disabled'])->default('pending');
            $table->enum('ownership_status', ['pending', 'checking', 'verified', 'failed', 'expired'])->default('pending');
            $table->enum('routing_status', ['pending', 'checking', 'ready', 'failed'])->default('pending');
            $table->enum('tls_status', ['pending', 'checking', 'ready', 'failed'])->default('pending');
            $table->enum('reachability_status', ['pending', 'checking', 'ready', 'failed'])->default('pending');
            $table->enum('operational_status', ['pending', 'checking', 'ready', 'failed', 'disabled'])->default('pending');
            $table->enum('verification_method', ['dns_txt'])->default('dns_txt');
            $table->char('verification_token_hash', 64)->nullable()->comment('Pending DNS verification challenge hash');
            $table->char('verified_token_hash', 64)->nullable()->comment('Last successfully verified DNS ownership proof hash');
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_verification_attempt_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('verification_failure_count')->default(0);
            $table->string('verification_error_code', 100)->nullable();
            $table->string('verification_error_message', 500)->nullable();
            $table->timestamp('revalidation_due_at')->nullable();
            $table->timestamp('verification_grace_expires_at')->nullable();
            $table->text('operational_probe_token')->nullable()->comment('Encrypted route probe token');
            $table->char('operational_probe_token_hash', 64)->nullable();
            $table->timestamp('last_operational_check_at')->nullable();
            $table->timestamp('operational_retry_at')->nullable();
            $table->timestamp('tls_expires_at')->nullable();
            $table->string('operational_error_code', 100)->nullable();
            $table->string('operational_error_message', 500)->nullable();
            $table->uuid('operational_claim_token')->nullable();
            $table->timestamp('operational_claimed_at')->nullable();
            $table->timestamp('operational_claim_lease_expires_at')->nullable();
            $table->uuid('revalidation_claim_token')->nullable();
            $table->timestamp('revalidation_claimed_at')->nullable();
            $table->timestamp('revalidation_claim_lease_expires_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()->index('tenant_domains_verified_by_ix');
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_domains_created_by_ix');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_domains_updated_by_ix');
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'tenant_domains_tenant_status_ix');
            $table->index(['tenant_id', 'operational_status'], 'tenant_domains_tenant_operational_ix');
            $table->index(['operational_status', 'operational_retry_at', 'operational_claimed_at'], 'tenant_domains_operational_retry_ix', );
            $table->index(['status', 'revalidation_due_at', 'revalidation_claimed_at'], 'tenant_domains_revalidation_ix', );
            $table->unique(['id', 'tenant_id'], 'tenant_domains_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
