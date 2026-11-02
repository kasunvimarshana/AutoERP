<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_offer_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'promotion_assignments_tenant_fk')->restrictOnDelete();
            $table->foreignId('offer_id');
            $table->foreignId('customer_id');
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('card_id')->nullable();
            $table->string('subject_key', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'offer_id', 'subject_key'], 'promotion_assignments_subject_uk');
            $table->unique(['id', 'tenant_id'], 'promotion_assignments_id_tenant_uk');
            $table->index(['tenant_id', 'customer_id', 'status'], 'promotion_assignments_customer_ix');
            $table->index(['tenant_id', 'vehicle_id', 'status'], 'promotion_assignments_vehicle_ix');
            $table->foreign(['offer_id', 'tenant_id'], 'promotion_assignments_offer_fk')->references(['id', 'tenant_id'])->on('promotion_offers')->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'promotion_assignments_customer_fk')->references(['id', 'tenant_id'])->on('customers')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'promotion_assignments_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
            $table->foreign(['card_id', 'tenant_id'], 'promotion_assignments_card_fk')->references(['id', 'tenant_id'])->on('promotion_cards')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'promotion_assignments_user_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_offer_assignments');
    }
};
