<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_lessee_agreement_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id', 'vehicle_rental_lessee_agreement_credit_notes_ou_fk')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('lessee_agreement_id')->constrained('vehicle_rental_lessee_agreements', 'id', 'vehicle_rental_lessee_agreement_credit_notes_agreement_fk')->cascadeOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            // GL account references
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('amount', 20, 4);
            $table->date('entry_date');

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_lessee_agreement_credit_notes');
    }
};
