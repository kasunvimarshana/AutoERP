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
            $table->boolean('is_primary')->default(false);
            $table->string('primary_marker', 16)->nullable();
            $table->enum('status', ['pending', 'active', 'disabled'])->default('pending');
            $table->enum('verification_method', ['dns_txt'])->default('dns_txt');
            $table->char('verification_token_hash', 64)->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable()->index('tenant_domains_verified_by_idx');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_domains_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_domains_updated_by_idx');
            $table->timestamps();
            $table->unique(['tenant_id', 'primary_marker'], 'tenant_domains_one_primary_uk');
            $table->index(['tenant_id', 'status'], 'tenant_domains_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_domains');
    }
};
