<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_document_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->unsignedBigInteger('document_id');
            $table->foreignId('document_definition_id')
                ->nullable()
                ->constrained('document_definitions')
                ->nullOnDelete();
            $table->string('document_role')->default('primary');
            $table->string('status')->default('linked');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->timestamp('linked_at')->nullable();

            $table->timestamps();

            $table->unique(['voucher_id', 'document_id', 'document_role'], 'voucher_document_links_uk');
            $table->index(['tenant_id', 'document_id'], 'voucher_document_links_document_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_document_links');
    }
};
