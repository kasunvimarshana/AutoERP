<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Supplier\Models\Supplier;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierBlockerService
{
    public function delete(Supplier $supplier): void
    {
        foreach (['purchase_orders', 'goods_receipt_notes', 'purchase_returns', 'purchase_debit_notes', 'supplier_vehicles', 'supplier_tax_profiles'] as $table) {
            if ($this->referenced($table, 'supplier_id', (int) $supplier->getKey())) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        foreach (['invoices', 'payments', 'rental_reservations', 'rental_agreements', 'rental_billing_periods', 'rental_charge_runs', 'rental_charge_calculations'] as $table) {
            if ($this->partyReferenced($table, (int) $supplier->getKey(), 'supplier')) {
                throw new ConflictHttpException('Supplier is referenced by business history and cannot be deleted. Deactivate the supplier instead.');
            }
        }
        $supplier->delete();
    }

    private function referenced(string $table, string $column, int $id): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $id)->exists();
    }

    private function partyReferenced(string $table, int $id, string $type): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'party_id') && DB::table($table)->where('party_id', $id)->whereIn('party_type', [$type, Supplier::class])->exists();
    }
}
