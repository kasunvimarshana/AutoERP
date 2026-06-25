<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'customer_status_histories_tenant_org_idx');
            $table->index('customer_id', 'customer_status_histories_customer_idx');
            $table->index('new_status', 'customer_status_histories_status_idx');

            $table->unique(['id', 'tenant_id'], 'customer_status_histories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customer_status_histories_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'customer_status_histories_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_status_histories');
    }
};
