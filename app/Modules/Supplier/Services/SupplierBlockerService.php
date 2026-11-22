<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Models\Supplier;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierBlockerService
{
    public function delete(Supplier $supplier): void
    {
        $tables = [
            'purchase_orders',
            'goods_receipt_notes',
            'purchase_returns',
            'purchase_debit_notes',
            'supplier_tax_profiles',
            'vehicle_rental_owner_agreements',
            'vehicle_rental_finance_agreements',
            'vehicle_rental_expenses',
        ];

        foreach ($tables as $table) {
            if ($this->referenced($table, 'supplier_id', (int) $supplier->getKey(), (int) $supplier->tenant_id)) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        foreach (['invoices', 'payments'] as $table) {
            if ($this->partyReferenced($table, (int) $supplier->getKey(), 'supplier', (int) $supplier->tenant_id)) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        if (Schema::hasTable('vehicle_ownerships') && DB::table('vehicle_ownerships')
            ->where('tenant_id', (int) $supplier->tenant_id)
            ->where('owner_type', 'supplier')
            ->where('owner_id', (int) $supplier->getKey())
            ->exists()) {
            throw new ConflictHttpException('Supplier is referenced by vehicle ownership history and cannot be deleted. Deactivate the supplier instead.');
        }
        $supplier->delete();
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
                ->whereIn('party_type', [$type, Supplier::class])
                ->exists();
    }
}
