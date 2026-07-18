<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Child-first order prevents foreign-key violations while removing the
     * retired Vehicle Rental schema.
     *
     * @var list<string>
     */
    private const TABLES_IN_DROP_ORDER = [
        'rental_usage_facts',
        'rental_calculation_sources',
        'rental_status_histories',
        'rental_deposit_links',
        'rental_deposit_requirements',
        'rental_calculation_lines',
        'rental_calculation_runs',
        'rental_billing_periods',
        'rental_expense_allocations',
        'rental_expenses',
        'rental_usage_contexts',
        'rental_usage_events',
        'rental_usage_logs',
        'rental_custody_event_items',
        'rental_custody_events',
        'rental_vehicle_replacements',
        'rental_driver_assignments',
        'rental_vehicle_allocations',
        'vehicle_finance_status_histories',
        'vehicle_finance_installments',
        'vehicle_finance_agreements',
        'rental_agreement_rate_components',
        'rental_agreement_rate_versions',
        'rental_agreement_terms',
        'rental_agreements',
        'rental_reservations',
    ];

    public function up(): void
    {
        $nonEmptyTables = [];

        foreach (self::TABLES_IN_DROP_ORDER as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                $nonEmptyTables[] = $table;
            }
        }

        if ($nonEmptyTables !== []) {
            throw new \RuntimeException(
                'Vehicle Rental removal stopped because operational data still exists in: '
                .implode(', ', $nonEmptyTables)
                .'. Export and verify the required archive, then explicitly purge the retired rental data before rerunning migrations. '
                .'Posted Invoice, Payment, Tax, and Finance records must not be deleted.',
            );
        }

        foreach (self::TABLES_IN_DROP_ORDER as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        throw new \LogicException(
            'Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.',
        );
    }
};
