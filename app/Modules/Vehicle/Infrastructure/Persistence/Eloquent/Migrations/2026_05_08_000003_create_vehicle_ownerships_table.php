<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_ownerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles', 'id')->cascadeOnDelete();
            $table->string('ownership_type')->comment('own, customer, supplier, provider, leased, financed, partner, internal, external, other');
            $table->string('owner_type')->comment('company, customer, supplier, employee, partner, external_party, party, other');
            $table->unsignedBigInteger('owner_id')->nullable()->comment('System record id when owner_type references a module record');
            $table->unsignedBigInteger('party_id')->nullable()->comment('Future BusinessParty/Party id when available');
            $table->string('owner_name')->nullable()->comment('External or non-system owner display name');
            $table->string('ownership_role')->default('legal_owner')->comment('legal_owner, registered_owner, operational_owner, provider, current_holder');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id'], 'vehicle_ownerships_vehicle_idx');
            $table->index(['tenant_id', 'owner_type', 'owner_id'], 'vehicle_ownerships_owner_idx');
            $table->index(['tenant_id', 'vehicle_id', 'ownership_role', 'is_current'], 'vehicle_ownerships_current_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownerships');
    }
};
