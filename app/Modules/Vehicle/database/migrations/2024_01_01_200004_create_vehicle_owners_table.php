<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_owners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();

            // Polymorphic owner – can be customer, supplier, partner, org_unit, employee
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');

            // Mark the primary legal owner (for registration, insurance etc.)
            $table->boolean('is_primary')->default(false);

            $table->json('metadata')->nullable();
            $table->timestamps();

            // Ensure a vehicle can't have exactly the same owner twice
            $table->unique(['tenant_id', 'org_unit_id', 'vehicle_id', 'owner_type', 'owner_id'], 'vehicle_owners_uk');

            $table->index(['tenant_id', 'org_unit_id', 'owner_type', 'owner_id'], 'vehicle_owners_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_owners');
    }
};
