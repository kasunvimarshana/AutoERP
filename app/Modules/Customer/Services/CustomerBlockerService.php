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
        $tables = [
            'vehicle_service_jobs',
            'customer_tax_profiles',
            'sales',
            'vehicle_rental_customer_agreements',
            'vehicle_rental_reservations',
            'vehicle_rental_expense_allocations',
            'promotion_cards',
        ];

        foreach ($tables as $table) {
            if ($this->referenced($table, 'customer_id', (int) $customer->getKey(), (int) $customer->tenant_id)) {
                throw new ConflictHttpException('Customer is referenced by business history and cannot be deleted. Deactivate the customer instead.');
            }
        }
        foreach (['invoices', 'payments'] as $table) {
            if ($this->partyReferenced($table, (int) $customer->getKey(), 'customer', (int) $customer->tenant_id)) {
                throw new ConflictHttpException('Customer is referenced by business history and cannot be deleted. Deactivate the customer instead.');
            }
        }
        if (Schema::hasTable('vehicle_ownerships') && DB::table('vehicle_ownerships')
            ->where('tenant_id', (int) $customer->tenant_id)
            ->where('owner_type', 'customer')
            ->where('owner_id', (int) $customer->getKey())
            ->exists()) {
            throw new ConflictHttpException('Customer is referenced by vehicle ownership history and cannot be deleted. Deactivate the customer instead.');
        }
        $customer->delete();
    }

    private function referenced(string $table, string $column, int $id, int $tenantId): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where($column, $id)
                ->exists();
    }

    private function partyReferenced(string $table, int $id, string $type, int $tenantId): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('party_id', $id)
                ->whereIn('party_type', [$type, Customer::class])
                ->exists();
    }
}
