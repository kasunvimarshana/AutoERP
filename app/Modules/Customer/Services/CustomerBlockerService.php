<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customer\Models\Customer;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerBlockerService
{
    public function delete(Customer $customer): void
    {
        foreach (['sales_quotations', 'sales_orders', 'sales_deliveries', 'sales_returns', 'sales_credit_notes', 'vehicle_service_jobs', 'customer_vehicles', 'customer_tax_profiles', 'rental_reservations', 'rental_agreements', 'rental_usage_contexts', 'rental_expense_allocations'] as $table) {
            if ($this->referenced($table, 'customer_id', (int) $customer->getKey(), (int) $customer->tenant_id)) {
                throw new ConflictHttpException('Customer is referenced by business history and cannot be deleted. Deactivate the customer instead.');
            }
        }
        foreach (['invoices', 'payments'] as $table) {
            if ($this->partyReferenced($table, (int) $customer->getKey(), 'customer', (int) $customer->tenant_id)) {
                throw new ConflictHttpException('Customer is referenced by business history and cannot be deleted. Deactivate the customer instead.');
            }
        }
        $customer->delete();
    }

    private function referenced(string $table, string $column, int $id, int $tenantId): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, $column)
            && Schema::hasColumn($table, 'tenant_id')
            && DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where($column, $id)
                ->exists();
    }

    private function partyReferenced(string $table, int $id, string $type, int $tenantId): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'party_id')
            && Schema::hasColumn($table, 'tenant_id')
            && DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('party_id', $id)
                ->whereIn('party_type', [$type, Customer::class])
                ->exists();
    }
}
