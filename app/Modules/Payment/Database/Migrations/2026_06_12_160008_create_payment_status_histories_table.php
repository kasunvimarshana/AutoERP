<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('payment_id');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'changed_at'], 'payment_status_histories_payment_date_idx');
            $table->index(['tenant_id', 'organization_unit_id'], 'payment_status_histories_tenant_org_idx');

            $table->unique(['id', 'tenant_id'], 'payment_status_histories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'payment_status_histories_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['payment_id', 'tenant_id'], 'payment_status_histories_payment_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('payments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_histories');
    }
};
