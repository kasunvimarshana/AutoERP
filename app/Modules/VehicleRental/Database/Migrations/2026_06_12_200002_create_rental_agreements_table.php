<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->string('agreement_number', 100);
            $table->foreignId('reservation_id')->nullable()->constrained('rental_reservations')->nullOnDelete();
            $table->string('direction', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->string('rental_type', 30);
            $table->string('billing_cycle', 20);
            $table->string('billing_basis', 30)->default('contractual_period');
            $table->string('proration_rule', 30)->default('exact_day_count');
            $table->string('billing_timezone', 60)->default('UTC');
            $table->unsignedSmallInteger('billing_period_days')->nullable();
            $table->date('agreement_date');
            $table->dateTime('start_at');
            $table->dateTime('expected_end_at');
            $table->dateTime('actual_end_at')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->json('terms_snapshot')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'agreement_number'], 'rental_agreements_tenant_number_uk');
            $table->unique('reservation_id', 'rental_agreements_reservation_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_agreements_tenant_org_idx');
            $table->index(['party_type', 'party_id'], 'rental_agreements_party_idx');
            $table->index(['status', 'start_at', 'expected_end_at'], 'rental_agreements_status_period_idx');
            $table->index(['direction', 'rental_type'], 'rental_agreements_direction_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};
