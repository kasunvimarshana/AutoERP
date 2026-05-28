<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_status_histories', function (Blueprint $table) {
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
            $table->string('from_status', 60)->nullable();
            $table->string('to_status', 60);
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->timestamps();

            $table->index(['tenant_id', 'customer_id', 'changed_at'], 'customer_status_histories_customer_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_status_histories');
    }
};
