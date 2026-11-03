<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_redemption_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_redemptions_tenant_fk')->restrictOnDelete();
            $table->foreignId('card_id')->nullable();
            $table->foreignId('card_benefit_id')->nullable();
            $table->foreignId('offer_assignment_id')->nullable();
            $table->foreignId('invoice_line_id');
            $table->string('event_type', 20);
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price_snapshot', 20, 6);
            $table->decimal('discount_amount_snapshot', 20, 6);
            $table->foreignId('reverses_event_id')->nullable();
            $table->foreignId('actor_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['invoice_line_id', 'event_type'], 'promotion_redemptions_invoice_line_uk');
            $table->unique('reverses_event_id', 'promotion_redemptions_reversal_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_redemptions_id_tenant_uk');
            $table->index(['tenant_id', 'card_id', 'card_benefit_id'], 'promotion_redemptions_card_benefit_ix');
            $table->index(['tenant_id', 'offer_assignment_id'], 'promotion_redemptions_assignment_ix');
            $table->foreign(['card_id', 'tenant_id'], 'promotion_redemptions_card_fk')->references(['id', 'tenant_id'])->on('promotion_cards')->restrictOnDelete();
            $table->foreign(['card_benefit_id', 'tenant_id'], 'promotion_redemptions_benefit_fk')->references(['id', 'tenant_id'])->on('promotion_card_benefits')->restrictOnDelete();
            $table->foreign(['offer_assignment_id', 'tenant_id'], 'promotion_redemptions_assignment_fk')->references(['id', 'tenant_id'])->on('promotion_offer_assignments')->restrictOnDelete();
            $table->foreign(['invoice_line_id', 'tenant_id'], 'promotion_redemptions_invoice_line_fk')->references(['id', 'tenant_id'])->on('invoice_lines')->restrictOnDelete();
            $table->foreign(['reverses_event_id', 'tenant_id'], 'promotion_redemptions_reverses_fk')->references(['id', 'tenant_id'])->on('promotion_redemption_events')->restrictOnDelete();
            $table->foreign(['actor_id', 'tenant_id'], 'promotion_redemptions_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemption_events');
    }
};
