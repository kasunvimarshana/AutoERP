<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_cards_tenant_fk')->restrictOnDelete();
            $table->foreignId('template_id');
            $table->string('card_number', 100);
            $table->foreignId('customer_id');
            $table->foreignId('issued_vehicle_id');
            $table->foreignId('vehicle_id');
            $table->date('issued_on');
            $table->date('expires_on');
            $table->string('status', 20);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('issued_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'card_number'], 'promotion_cards_tenant_number_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_cards_id_tenant_uk');
            $table->index(['tenant_id', 'customer_id', 'status'], 'promotion_cards_customer_ix');
            $table->index(['tenant_id', 'vehicle_id', 'status'], 'promotion_cards_vehicle_ix');
            $table->foreign(['template_id', 'tenant_id'], 'promotion_cards_template_fk')->references(['id', 'tenant_id'])->on('promotion_card_templates')->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'promotion_cards_customer_fk')->references(['id', 'tenant_id'])->on('customers')->restrictOnDelete();
            $table->foreign(['issued_vehicle_id', 'tenant_id'], 'promotion_cards_issued_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'promotion_cards_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['issued_by', 'tenant_id'], 'promotion_cards_issued_by_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_cards');
    }
};
