<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->string('line_type', 120)->default('line');
            $table->string('item_type');
            $table->string('item_label')->nullable();
            $table->string('description')->nullable();
            $table->decimal('quantity', 20, 6)->nullable();
            $table->string('uom_label', 80)->nullable();
            $table->decimal('unit_price', 20, 4)->nullable();
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->string('source_line_type', 120)->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_items_tenant_document_index');
            $table->index(['tenant_id', 'document_id', 'line_no'], 'document_items_line_no_idx');
            $table->index(['tenant_id', 'source_line_type', 'source_line_id'], 'document_items_source_line_idx');
            $table->index(['document_id', 'sequence'], 'document_items_document_sequence_index');
            $table->index(['item_type'], 'document_items_type_index');
            $table->index(['tenant_id', 'created_at'], 'document_items_tenant_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
