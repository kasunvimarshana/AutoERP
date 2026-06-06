<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_status_histories_tenant_org_idx');
            $table->index('supplier_id', 'supplier_status_histories_supplier_idx');
            $table->index('new_status', 'supplier_status_histories_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_status_histories');
    }
};
