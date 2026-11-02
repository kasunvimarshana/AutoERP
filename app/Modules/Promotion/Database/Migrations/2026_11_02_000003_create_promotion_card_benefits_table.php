<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_card_benefits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_card_benefits_tenant_fk')->restrictOnDelete();
            $table->foreignId('template_id');
            $table->unsignedInteger('position');
            $table->string('kind', 20);
            $table->foreignId('item_id')->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_rate', 20, 6)->nullable();
            $table->unsignedInteger('use_limit')->nullable();
            $table->foreignId('offer_id')->nullable();
            $table->json('valid_weekdays')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'position'], 'promotion_card_benefits_template_position_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_card_benefits_id_tenant_uk');
            $table->foreign(['template_id', 'tenant_id'], 'promotion_card_benefits_template_fk')->references(['id', 'tenant_id'])->on('promotion_card_templates')->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'promotion_card_benefits_item_fk')->references(['id', 'tenant_id'])->on('items')->restrictOnDelete();
            $table->foreign(['offer_id', 'tenant_id'], 'promotion_card_benefits_offer_fk')->references(['id', 'tenant_id'])->on('promotion_offers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_card_benefits');
    }
};
