<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_user_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('access_type', 60)->default('portal')->comment('portal, api, integrations');
            $table->string('status', 40)->default('active')->comment('active, inactive, revoked');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'supplier_id', 'user_id'], 'supplier_user_accounts_supplier_user_uk');
            $table->index(['tenant_id', 'supplier_id', 'status'], 'supplier_user_accounts_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_user_accounts');
    }
};
