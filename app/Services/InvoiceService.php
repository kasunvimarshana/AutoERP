<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::where('tenant_id', $tenantId)->with('items');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['status'] ??= InvoiceStatus::Draft;
            $data['invoice_number'] ??= 'INV-'.strtoupper(Str::random(8));
            $data['issue_date'] ??= today()->toDateString();

            [$subtotal, $taxAmount, $total] = $this->calculateTotals($items, $data['discount_amount'] ?? '0');
            $data['subtotal'] = $subtotal;
            $data['tax_amount'] = $taxAmount;
            $data['total'] = $total;
            $data['amount_due'] = $total;
            $data['amount_paid'] = '0';

            $invoice = Invoice::create($data);

            foreach ($items as $item) {
                $item['invoice_id'] = $invoice->id;
                $item['line_total'] = $this->calculateLineTotal($item);
                $invoice->items()->create($item);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: Invoice::class,
                auditableId: $invoice->id,
                newValues: ['invoice_number' => $invoice->invoice_number, 'total' => $invoice->total]
            );

            return $invoice->fresh(['items']);
        });
    }

    public function send(string $id): Invoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = Invoice::lockForUpdate()->findOrFail($id);

            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new \RuntimeException('Only draft invoices can be sent.');
            }

            $invoice->update([
                'status' => InvoiceStatus::Sent,
                'sent_at' => now(),
            ]);

            return $invoice->fresh();
        });
    }

    public function void(string $id): Invoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = Invoice::lockForUpdate()->findOrFail($id);

            if ($invoice->status === InvoiceStatus::Paid) {
                throw new \RuntimeException('Cannot void a fully paid invoice.');
            }

            $invoice->update([
                'status' => InvoiceStatus::Void,
                'voided_at' => now(),
            ]);

            return $invoice->fresh();
        });
    }

    private function calculateTotals(array $items, string $discount): array
    {
        $subtotal = '0';
        $taxAmount = '0';

        foreach ($items as $item) {
            $qty = (string) ($item['quantity'] ?? '1');
            $unitPrice = (string) ($item['unit_price'] ?? '0');
            $discAmt = (string) ($item['discount_amount'] ?? '0');
            $taxRate = (string) ($item['tax_rate'] ?? '0');

            $lineBase = bcsub(bcmul($qty, $unitPrice, 8), $discAmt, 8);
            $lineTax = bcdiv(bcmul($lineBase, $taxRate, 8), '100', 8);

            $subtotal = bcadd($subtotal, $lineBase, 8);
            $taxAmount = bcadd($taxAmount, $lineTax, 8);
        }

        $total = bcadd(bcsub($subtotal, $discount, 8), $taxAmount, 8);

        return [$subtotal, $taxAmount, $total];
    }

    private function calculateLineTotal(array $item): string
    {
        $qty = (string) ($item['quantity'] ?? '1');
        $unitPrice = (string) ($item['unit_price'] ?? '0');
        $discAmt = (string) ($item['discount_amount'] ?? '0');
        $taxAmt = (string) ($item['tax_amount'] ?? '0');

        return bcadd(bcsub(bcmul($qty, $unitPrice, 8), $discAmt, 8), $taxAmt, 8);
    }
}
