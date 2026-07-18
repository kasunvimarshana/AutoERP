<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertTableEmpty('rental_usage_facts');
        $this->assertTableEmpty('rental_calculation_sources');
        $this->assertTableEmpty('rental_status_histories');
        $this->assertTableEmpty('rental_deposit_links');
        $this->assertTableEmpty('rental_deposit_requirements');
        $this->assertTableEmpty('rental_calculation_lines');
        $this->assertTableEmpty('rental_calculation_runs');
        $this->assertTableEmpty('rental_billing_periods');
        $this->assertTableEmpty('rental_expense_allocations');
        $this->assertTableEmpty('rental_expenses');
        $this->assertTableEmpty('rental_usage_contexts');
        $this->assertTableEmpty('rental_usage_events');
        $this->assertTableEmpty('rental_usage_logs');
        $this->assertTableEmpty('rental_custody_event_items');
        $this->assertTableEmpty('rental_custody_events');
        $this->assertTableEmpty('rental_vehicle_replacements');
        $this->assertTableEmpty('rental_driver_assignments');
        $this->assertTableEmpty('rental_vehicle_allocations');
        $this->assertTableEmpty('vehicle_finance_status_histories');
        $this->assertTableEmpty('vehicle_finance_installments');
        $this->assertTableEmpty('vehicle_finance_agreements');
        $this->assertTableEmpty('rental_agreement_rate_components');
        $this->assertTableEmpty('rental_agreement_rate_versions');
        $this->assertTableEmpty('rental_agreement_terms');
        $this->assertTableEmpty('rental_agreements');
        $this->assertTableEmpty('rental_reservations');
    }

    public function down(): void
    {
        throw new \LogicException(
            'Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.',
        );
    }

    private function assertTableEmpty(string $table): void
    {
        if (! Schema::hasTable($table) || ! DB::table($table)->exists()) {
            return;
        }

        throw new \RuntimeException(
            "Vehicle Rental removal stopped because operational data still exists in [{$table}]. "
            .'Export and verify the required archive, then explicitly purge the retired rental data before rerunning migrations. '
            .'Posted Invoice, Payment, Tax, and Finance records must not be deleted.',
        );
    }
};
