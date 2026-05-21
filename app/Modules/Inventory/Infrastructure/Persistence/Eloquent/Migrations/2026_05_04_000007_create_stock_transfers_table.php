<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference_number');
            $table->foreignId('from_location_id')->constrained('warehouse_locations');
            $table->foreignId('to_location_id')->constrained('warehouse_locations');
            $table->string('status')->default('draft');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number'], 'stock_transfers_reference_number_uk');
            $table->index(['tenant_id', 'status'], 'stock_transfers_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
