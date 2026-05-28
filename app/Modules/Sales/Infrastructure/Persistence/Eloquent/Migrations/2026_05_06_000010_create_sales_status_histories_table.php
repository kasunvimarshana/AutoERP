<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_status_histories', function (Blueprint $table): void {
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

            $table->string('entity_type')->comment('sales_order, gdn_header, sales_return, sales_document');
            $table->unsignedBigInteger('entity_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'sales_status_histories_entity_idx');
            $table->index(['tenant_id', 'to_status', 'changed_at'], 'sales_status_histories_status_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_status_histories');
    }
};
