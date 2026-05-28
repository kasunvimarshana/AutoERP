<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_document_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('running_chart_id')
                ->nullable()
                ->constrained('vehicle_rental_running_charts')
                ->cascadeOnDelete();
            $table->foreignId('replacement_id')
                ->nullable()
                ->constrained('vehicle_rental_replacements')
                ->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->string('document_role');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->dateTime('linked_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['tenant_id', 'document_id', 'entity_type', 'entity_id'],
                'vehicle_rental_document_links_uk',
            );
            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_document_links_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_document_links');
    }
};
