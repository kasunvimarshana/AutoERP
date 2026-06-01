<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_ledger_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('source_type', 80)->comment('purchase_order, grn_header, purchase_invoice, purchase_payment, purchase_return, purchase_refund');
            $table->unsignedBigInteger('source_id');
            $table->string('source_reference')->nullable();
            $table->string('note_type', 80)->default('manual')->comment('manual, workflow, finance, inventory, payment, document');
            $table->text('body');
            $table->boolean('is_visible_to_api')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'source_type', 'source_id'], 'purchase_ledger_notes_source_idx');
            $table->index(['tenant_id', 'note_type', 'created_at'], 'purchase_ledger_notes_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_ledger_notes');
    }
};
