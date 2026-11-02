<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_qualification_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_qualifications_tenant_fk')->restrictOnDelete();
            $table->foreignId('offer_assignment_id');
            $table->foreignId('invoice_line_id');
            $table->string('event_type', 20);
            $table->decimal('quantity', 20, 6);
            $table->decimal('charged_amount_snapshot', 20, 6);
            $table->foreignId('reverses_event_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['offer_assignment_id', 'invoice_line_id', 'event_type'], 'promotion_qualifications_source_uk');
            $table->unique('reverses_event_id', 'promotion_qualifications_reversal_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_qualifications_id_tenant_uk');
            $table->index(['tenant_id', 'offer_assignment_id', 'occurred_at'], 'promotion_qualifications_progress_ix');
            $table->foreign(['offer_assignment_id', 'tenant_id'], 'promotion_qualifications_assignment_fk')->references(['id', 'tenant_id'])->on('promotion_offer_assignments')->restrictOnDelete();
            $table->foreign(['invoice_line_id', 'tenant_id'], 'promotion_qualifications_invoice_line_fk')->references(['id', 'tenant_id'])->on('invoice_lines')->restrictOnDelete();
            $table->foreign(['reverses_event_id', 'tenant_id'], 'promotion_qualifications_reverses_fk')->references(['id', 'tenant_id'])->on('promotion_qualification_events')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_qualification_events');
    }
};
