<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_customer_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->string('reference', AgreementFields::REFERENCE_LENGTH);
            $table->foreignId('customer_id');
            $table->string('party_name_snapshot');
            $table->string('party_code_snapshot')->nullable();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('currency_code_snapshot');
            $table->date('agreed_on');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('basis');
            $table->string('driver_mode');
            $table->string('status')->default(AgreementStatus::Draft->value);
            $table->json('terms');
            $table->text('notes')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'reference'], 'vrca_reference_uk');
            $table->unique(['id', 'tenant_id'], 'vrca_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'vrca_context_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vrca_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'vrca_party_fk')->references(['id', 'tenant_id'])->on('customers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_customer_agreements');
    }
};
