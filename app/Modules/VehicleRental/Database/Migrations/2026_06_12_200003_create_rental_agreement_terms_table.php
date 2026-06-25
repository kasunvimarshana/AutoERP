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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
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
            $table->index(['agreement_id', 'is_active'], 'rental_agreement_terms_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_terms');
    }
};
