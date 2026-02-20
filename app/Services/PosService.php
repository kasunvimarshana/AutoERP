<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterTransaction;
use App\Models\PosTransaction;
use App\Models\PosTransactionLine;
use App\Models\PosTransactionPayment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventoryService $inventoryService
    ) {}

    // Cash Register management

    public function paginateRegisters(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CashRegister::where('tenant_id', $tenantId)
            ->with(['businessLocation', 'openedBy', 'closedBy']);

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function openRegister(array $data, string $userId): CashRegister
    {
        return DB::transaction(function () use ($data, $userId) {
            $register = CashRegister::findOrFail($data['cash_register_id']);

            if ($register->status === CashRegisterStatus::Open->value) {
                throw new \RuntimeException('Cash register is already open.');
            }

            $register->update([
                'status' => CashRegisterStatus::Open->value,
                'opened_by' => $userId,
                'opened_at' => now(),
                'closed_by' => null,
                'closed_at' => null,
            ]);

            CashRegisterTransaction::create([
                'cash_register_id' => $register->id,
                'type' => 'opening',
                'amount' => $data['opening_amount'] ?? '0',
                'note' => 'Register opened',
                'created_by' => $userId,
            ]);

            return $register->fresh(['businessLocation', 'openedBy']);
        });
    }

    public function closeRegister(array $data, string $userId): CashRegister
    {
        return DB::transaction(function () use ($data, $userId) {
            $register = CashRegister::lockForUpdate()->findOrFail($data['cash_register_id']);

            if ($register->status === CashRegisterStatus::Closed->value) {
                throw new \RuntimeException('Cash register is already closed.');
            }

            $register->update([
                'status' => CashRegisterStatus::Closed->value,
                'closing_balance' => $data['closing_balance'] ?? '0',
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            CashRegisterTransaction::create([
                'cash_register_id' => $register->id,
                'type' => 'closing',
                'amount' => $data['closing_balance'] ?? '0',
                'note' => $data['note'] ?? 'Register closed',
                'created_by' => $userId,
            ]);

            return $register->fresh(['businessLocation', 'closedBy']);
        });
    }

    public function cashInOut(array $data, string $userId): CashRegisterTransaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $register = CashRegister::findOrFail($data['cash_register_id']);

            if ($register->status !== CashRegisterStatus::Open->value) {
                throw new \RuntimeException('Cash register must be open to record cash in/out.');
            }

            return CashRegisterTransaction::create([
                'cash_register_id' => $register->id,
                'type' => $data['type'], // pay_in or pay_out
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    // POS Transaction management

    public function paginateTransactions(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PosTransaction::where('tenant_id', $tenantId)
            ->with(['businessLocation', 'customer', 'createdBy']);

        if (isset($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function createTransaction(array $data, string $tenantId, string $userId): PosTransaction
    {
        return DB::transaction(function () use ($data, $tenantId, $userId) {
            // Calculate totals using BCMath for precision
            $subtotal = '0';
            $taxAmount = '0';
            $discountAmount = $data['discount_amount'] ?? '0';

            $lines = $data['lines'] ?? [];
            foreach ($lines as $line) {
                $lineTotal = bcmul($line['unit_price'], $line['quantity'], 8);
                $lineDiscount = bcmul($lineTotal, bcdiv($line['discount_percent'] ?? '0', '100', 8), 8);
                $lineTaxBase = bcsub($lineTotal, $lineDiscount, 8);
                $lineTax = bcmul($lineTaxBase, bcdiv($line['tax_percent'] ?? '0', '100', 8), 8);
                $subtotal = bcadd($subtotal, $lineTotal, 8);
                $taxAmount = bcadd($taxAmount, $lineTax, 8);
            }

            $total = bcadd(bcsub($subtotal, $discountAmount, 8), $taxAmount, 8);
            $paidAmount = '0';
            foreach ($data['payments'] ?? [] as $payment) {
                $paidAmount = bcadd($paidAmount, $payment['amount'], 8);
            }
            $changeAmount = bcsub($paidAmount, $total, 8);

            $transaction = PosTransaction::create([
                'tenant_id' => $tenantId,
                'business_location_id' => $data['business_location_id'],
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? 'POS-'.strtoupper(Str::random(8)),
                'status' => $data['status'] ?? 'completed',
                'customer_id' => $data['customer_id'] ?? null,
                'customer_group_id' => $data['customer_group_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => bccomp($changeAmount, '0', 8) >= 0 ? $changeAmount : '0',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Create transaction lines and update inventory
            foreach ($lines as $line) {
                $lineTotal = bcmul($line['unit_price'], $line['quantity'], 8);
                $lineDiscount = bcmul($lineTotal, bcdiv($line['discount_percent'] ?? '0', '100', 8), 8);
                $lineTaxBase = bcsub($lineTotal, $lineDiscount, 8);
                $lineTax = bcmul($lineTaxBase, bcdiv($line['tax_percent'] ?? '0', '100', 8), 8);
                $finalLineTotal = bcadd(bcsub($lineTotal, $lineDiscount, 8), $lineTax, 8);

                PosTransactionLine::create([
                    'pos_transaction_id' => $transaction->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'] ?? '0',
                    'discount_amount' => $lineDiscount,
                    'tax_percent' => $line['tax_percent'] ?? '0',
                    'tax_amount' => $lineTax,
                    'line_total' => $finalLineTotal,
                    'modifiers' => $line['modifiers'] ?? null,
                ]);

                // Decrement inventory for completed transactions
                if ($transaction->status === 'completed') {
                    $this->inventoryService->adjust(
                        tenantId: $tenantId,
                        warehouseId: $data['warehouse_id'] ?? $data['business_location_id'],
                        productId: $line['product_id'],
                        quantity: '-'.$line['quantity'],
                        movementType: 'pos_sale',
                        variantId: $line['product_variant_id'] ?? null,
                        notes: 'POS sale: '.$transaction->reference_no,
                        referenceType: PosTransaction::class,
                        referenceId: $transaction->id
                    );
                }
            }

            // Create payment records
            foreach ($data['payments'] ?? [] as $payment) {
                PosTransactionPayment::create([
                    'pos_transaction_id' => $transaction->id,
                    'payment_account_id' => $payment['payment_account_id'] ?? null,
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                    'metadata' => $payment['metadata'] ?? null,
                ]);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: PosTransaction::class,
                auditableId: $transaction->id,
                newValues: ['reference_no' => $transaction->reference_no, 'total' => $total]
            );

            return $transaction->fresh(['lines', 'transactionPayments', 'customer']);
        });
    }

    public function voidTransaction(string $id, string $tenantId): PosTransaction
    {
        return DB::transaction(function () use ($id, $tenantId) {
            $transaction = PosTransaction::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($transaction->status !== 'completed') {
                throw new \RuntimeException('Only completed transactions can be voided.');
            }

            $transaction->update(['status' => 'void']);

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: PosTransaction::class,
                auditableId: $transaction->id,
                oldValues: ['status' => 'completed'],
                newValues: ['status' => 'void']
            );

            return $transaction->fresh();
        });
    }
}
