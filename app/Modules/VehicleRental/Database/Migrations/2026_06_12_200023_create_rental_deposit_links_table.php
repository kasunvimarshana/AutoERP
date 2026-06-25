<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_deposit_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('deposit_requirement_id');
            $table->string('link_type', 30);
            $table->foreignId('payment_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->decimal('amount', 20, 6);
            $table->string('status', 20)->default('active');
            $table->dateTime('linked_at');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->foreignId('reverses_link_id')->nullable();
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'fingerprint'], 'rental_deposit_links_fingerprint_uk');
            $table->index(['deposit_requirement_id', 'status', 'link_type'], 'rental_deposit_links_requirement_idx');
            $table->index(['payment_id', 'status'], 'rental_deposit_links_payment_idx');
            $table->index(['invoice_id', 'status'], 'rental_deposit_links_invoice_idx');

            $table->unique(['id', 'tenant_id'], 'rental_deposit_links_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_deposit_links_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['deposit_requirement_id', 'tenant_id'], 'rental_deposit_links_deposit_requirement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_deposit_requirements')
                ->cascadeOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'rental_deposit_links_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'rental_deposit_links_invoice_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();
            $table->foreign(['reverses_link_id', 'tenant_id'], 'rental_deposit_links_reverses_link_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_deposit_links')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_deposit_links_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_deposit_links_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_deposit_links');
    }
};
