<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_party_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('source_party_type', 80)->comment('customer, supplier, employee, user, company, external_party, provider, party, other');
            $table->unsignedBigInteger('source_party_id')->nullable();
            $table->string('source_party_name')->nullable()->comment('External/non-system source display name');
            $table->string('target_party_type', 80)->comment('customer, supplier, employee, user, company, external_party, provider, party, other');
            $table->unsignedBigInteger('target_party_id')->nullable();
            $table->string('target_party_name')->nullable()->comment('External/non-system target display name');
            $table->string('relation_type', 80)->comment('same_party, acts_as, billing_relation, provider_relation, payer_relation, payee_relation');
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('source_context')->nullable()->comment('Generic context supplied by the caller; not workflow logic');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'source_party_type', 'source_party_id'], 'business_party_links_source_idx');
            $table->index(['tenant_id', 'target_party_type', 'target_party_id'], 'business_party_links_target_idx');
            $table->index(['tenant_id', 'relation_type', 'is_active'], 'business_party_links_relation_idx');
            $table->index([
                'tenant_id',
                'source_party_type',
                'source_party_id',
                'target_party_type',
                'target_party_id',
                'relation_type',
                'is_active',
            ], 'business_party_links_source_target_relation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_party_links');
    }
};
