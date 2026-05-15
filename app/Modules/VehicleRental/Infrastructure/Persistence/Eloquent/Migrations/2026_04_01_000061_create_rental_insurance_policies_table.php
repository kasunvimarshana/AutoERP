<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('policy_type');
            $table->string('policy_number')->nullable();
            $table->string('provider')->nullable();
            $table->decimal('premium', 20, 4)->default(0);
            $table->decimal('coverage_amount', 20, 4)->nullable();
            $table->decimal('deductible', 20, 4)->nullable();
            $table->date('effective_date');
            $table->date('expiry_date');
            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('rental_insurance_policies'); }
};
