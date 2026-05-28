<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_user_accounts', function (Blueprint $table) {
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

            $table->foreignId('customer_id')->constrained('customers', 'id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('access_role', 60)->default('customer_portal');
            $table->boolean('is_primary')->default(false);
            $table->string('access_status', 40)->default('active')->comment('active, invited, inactive, revoked');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('linked_user_by')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'customer_id', 'user_id'], 'customer_user_accounts_customer_user_uk');
            $table->index(['tenant_id', 'customer_id', 'access_status'], 'customer_user_accounts_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user_accounts');
    }
};