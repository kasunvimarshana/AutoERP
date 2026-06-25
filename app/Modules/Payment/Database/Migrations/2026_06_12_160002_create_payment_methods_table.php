<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('scope_key', 160);
            $table->string('code', 100);
            $table->string('name');
            $table->enum('method_type', [
                'cash',
                'cheque',
                'bank_transfer',
                'card',
                'mobile_wallet',
                'digital_wallet',
                'direct_debit',
                'other',
            ]);
            $table->enum('direction_allowed', ['inbound', 'outbound', 'both'])->default('both');
            $table->boolean('requires_reference')->default(false);
            $table->boolean('requires_bank_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['scope_key', 'code'], 'payment_methods_scope_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'payment_methods_tenant_org_idx');
            $table->index(['method_type', 'direction_allowed', 'is_active'], 'payment_methods_type_direction_active_idx');

            $table->unique(['id', 'tenant_id'], 'payment_methods_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_methods_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
