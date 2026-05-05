<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_return_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('rental_id')->constrained('fleet_rentals', 'id', 'fret_rental_fk');
            $table->string('return_number');
            $table->enum('status', ['pending', 'inspected', 'refunded', 'partial_refund', 'no_refund'])->default('pending');
            $table->dateTime('returned_at');
            $table->decimal('end_odometer', 12, 2)->nullable();
            $table->decimal('actual_days', 10, 4)->nullable();
            $table->decimal('rental_charge', 20, 6)->default(0);
            $table->decimal('extra_charges', 20, 6)->default(0);
            $table->decimal('damage_charges', 20, 6)->default(0);
            $table->decimal('fuel_charges', 20, 6)->default(0);
            $table->decimal('deposit_paid', 20, 6)->default(0);
            $table->decimal('refund_amount', 20, 6)->default(0);
            $table->string('refund_method')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('notes')->nullable();
            $table->json('damage_photos')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'return_number'], 'fleet_return_refunds_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_return_refunds_tenant_status_idx');
            $table->index(['tenant_id', 'rental_id'], 'fleet_return_refunds_tenant_rental_idx');
            $table->index(['tenant_id', 'returned_at'], 'fleet_return_refunds_tenant_returned_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_return_refunds');
    }
};
