<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_count_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'cycle_count_headers_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses', 'id', 'cycle_count_headers_warehouse_id_fk');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id', 'cycle_count_headers_location_id_fk')->nullOnDelete();
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled']);
            $table->foreignId('counted_by_user_id')->nullable()->constrained('users', 'id', 'cycle_count_headers_counted_by_user_id_fk');
            $table->timestamp('counted_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id', 'cycle_count_headers_approved_by_user_id_fk');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_headers');
    }
};
