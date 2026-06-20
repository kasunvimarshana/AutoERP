<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('rental_reservations')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('rental_agreements')->nullOnDelete();
            $table->foreignId('usage_log_id')->nullable()->constrained('rental_usage_logs')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('rental_expenses')->nullOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained('rental_charges')->nullOnDelete();
            $table->foreignId('agreement_vehicle_link_id')->nullable()
                ->constrained('rental_agreement_vehicle_links', 'id', 'rsh_vehicle_link_fk')
                ->nullOnDelete();
            $table->string('entity_type', 20);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'rental_status_histories_tenant_org_idx');
            $table->index(['reservation_id', 'changed_at'], 'rental_status_histories_reservation_idx');
            $table->index(['agreement_id', 'changed_at'], 'rental_status_histories_agreement_idx');
            $table->index(['usage_log_id', 'changed_at'], 'rental_status_histories_usage_idx');
            $table->index(['expense_id', 'changed_at'], 'rental_status_histories_expense_idx');
            $table->index(['charge_id', 'changed_at'], 'rental_status_histories_charge_idx');
            $table->index(['agreement_vehicle_link_id', 'changed_at'], 'rental_status_histories_vehicle_link_idx');
            $table->index(['entity_type', 'subject_id', 'changed_at'], 'rental_status_histories_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_status_histories');
    }
};
