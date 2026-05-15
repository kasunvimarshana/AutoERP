<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_damages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('damage_type');
            $table->string('severity')->default('minor');
            $table->text('description');
            $table->decimal('estimated_repair_cost', 20, 4)->default(0);
            $table->decimal('actual_repair_cost', 20, 4)->nullable();
            $table->decimal('customer_liability', 20, 4)->default(0);
            $table->decimal('insurance_claim_amount', 20, 4)->nullable();
            $table->string('status')->default('reported');
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_damages');
    }
};
