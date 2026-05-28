<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->unsignedBigInteger('provider_payable_id')->nullable();
            $table->foreignId('document_link_id')
                ->nullable()
                ->constrained('vehicle_rental_document_links')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('payment_allocation_id')->nullable();
            $table->string('payment_direction')->comment('incoming, outgoing');
            $table->string('payment_role')
                ->comment('advance, deposit, settlement, refund, write_off, provider_payment');
            $table->string('status')->default('active');
            $table->decimal('amount', 20, 4)->default(0);
            $table->decimal('refund_amount', 20, 4)->default(0);
            $table->decimal('write_off_amount', 20, 4)->default(0);
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->dateTime('linked_at');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_payment_links_agreement_idx');
            $table->index(['tenant_id', 'provider_payable_id'], 'vehicle_rental_payment_links_payable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_payment_links');
    }
};
