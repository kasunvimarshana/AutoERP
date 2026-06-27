<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Modules\Supplier\Models\Supplier;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierBlockerService
{
    public function delete(Supplier $supplier): void
    {
        foreach (['purchase_orders', 'goods_receipt_notes', 'purchase_returns', 'purchase_debit_notes', 'supplier_tax_profiles', 'rental_agreements', 'vehicle_finance_agreements', 'rental_expenses', 'rental_usage_contexts', 'rental_expense_allocations'] as $table) {
            if ($this->referenced($table, 'supplier_id', (int) $supplier->getKey(), (int) $supplier->tenant_id)) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        if ($this->vehicleOwnershipReferenced((int) $supplier->getKey(), (int) $supplier->tenant_id)) {
            throw new ConflictHttpException('Supplier is referenced by vehicle ownership history and cannot be deleted. Deactivate the supplier instead.');
        }
        foreach (['invoices', 'payments'] as $table) {
            if ($this->partyReferenced($table, (int) $supplier->getKey(), 'supplier', (int) $supplier->tenant_id)) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        $supplier->delete();
    }

    private function referenced(string $table, string $column, int $id, int $tenantId): bool
    {
        return DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where($column, $id)
                ->exists();
    }

    private function vehicleOwnershipReferenced(int $id, int $tenantId): bool
    {
        return DB::table('vehicle_ownerships')
            ->where('tenant_id', $tenantId)
            ->where('owner_type', 'supplier')
            ->where('owner_id', $id)
            ->exists();
    }

    private function partyReferenced(string $table, int $id, string $type, int $tenantId): bool
    {
        return DB::table($table)
                ->where('tenant_id', $tenantId)
                ->where('party_id', $id)
                ->whereIn('party_type', [$type, Supplier::class])
                ->exists();
    }
}
