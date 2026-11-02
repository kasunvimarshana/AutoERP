<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_terms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'rental_agreement_terms_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id');
            $table->unsignedInteger('sequence');
            $table->string('term_code', 50)->nullable();
            $table->string('title', 150)->nullable();
            $table->text('content');
            $table->boolean('is_printable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['agreement_id', 'sequence'], 'rental_agreement_terms_sequence_uk');
            $table->index(['agreement_id', 'is_active'], 'rental_agreement_terms_active_ix');

            $table->unique(['id', 'tenant_id'], 'rental_agreement_terms_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_agreement_terms_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'rental_agreement_terms_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->cascadeOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_agreement_terms_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_agreement_terms_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_terms');
    }
};
