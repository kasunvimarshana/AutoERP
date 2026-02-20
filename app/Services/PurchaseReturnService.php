<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseReturn::where('tenant_id', $tenantId)
            ->with(['purchase', 'supplier', 'businessLocation', 'lines']);

        if (isset($filters['purchase_id'])) {
            $query->where('purchase_id', $filters['purchase_id']);
        }
        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('return_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('return_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('return_date')->paginate($perPage);
    }

    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            // Calculate totals using BCMath for precision
            $subtotal = '0.00000000';
            $taxAmount = '0.00000000';

            foreach ($lines as &$line) {
                $lineSubtotal = bcmul((string) $line['quantity'], (string) $line['unit_cost'], 8);
                $lineTax = bcmul($lineSubtotal, bcdiv((string) ($line['tax_percent'] ?? 0), '100', 8), 8);
                $line['tax_amount'] = $lineTax;
                $line['line_total'] = bcadd($lineSubtotal, $lineTax, 8);
                $subtotal = bcadd($subtotal, $lineSubtotal, 8);
                $taxAmount = bcadd($taxAmount, $lineTax, 8);
            }

            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $taxAmount;
            $data['total'] = bcadd($subtotal, $taxAmount, 8);

            $purchaseReturn = PurchaseReturn::create($data);

            foreach ($lines as $lineData) {
                $purchaseReturn->lines()->create($lineData);
            }

            return $purchaseReturn->fresh(['lines.product', 'supplier']);
        });
    }

    public function cancel(string $id): PurchaseReturn
    {
        return DB::transaction(function () use ($id) {
            $purchaseReturn = PurchaseReturn::findOrFail($id);

            if ($purchaseReturn->status === 'cancelled') {
                throw new \RuntimeException('Purchase return is already cancelled.');
            }

            $purchaseReturn->update(['status' => 'cancelled']);

            return $purchaseReturn->fresh();
        });
    }
}
