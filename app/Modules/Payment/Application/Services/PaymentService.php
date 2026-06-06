<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Services\FinancePostingService;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class PaymentService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly PaymentAllocationService $allocations,
        private readonly FinancePostingService $posting,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('payments')
            ->select(['id', 'payment_number', 'party_type', 'party_id', 'payment_date', 'amount', 'allocated_amount', 'direction', 'payment_method_id', 'status', 'created_at'])
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('status', (string) $filters['status']))
            ->when(isset($filters['direction']), fn (Builder $query): Builder => $query->where('direction', (string) $filters['direction']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('payment_number', 'like', "%$search%")->orWhere('reference', 'like', "%$search%")))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function find(int $paymentId): object
    {
        $payment = DB::table('payments')->where('tenant_id', $this->support->tenantId())->whereNull('deleted_at')->where('id', $paymentId)->first();
        if ($payment === null) {
            abort(404);
        }
        $payment->allocations = DB::table('payment_allocations')->where('tenant_id', $this->support->tenantId())->where('payment_id', $paymentId)->where('status', 'active')->get();

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function lookup(string $type, array $filters = []): array
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));
        $limit = min((int) ($filters['limit'] ?? 50), 100);
        $direction = match ($type) {
            'payable-invoices' => 'payable',
            'receivable-invoices' => 'receivable',
            'outstanding-invoices' => ($filters['direction'] ?? null) === 'outbound' ? 'payable' : 'receivable',
            default => throw ValidationException::withMessages(['type' => ['Unsupported lookup type.']]),
        };

        return DB::table('invoices')
            ->select([
                'id',
                'invoice_number as code',
                'invoice_number as name',
                'document_type',
                'ledger_direction',
                'customer_id',
                'supplier_id',
                'invoice_date',
                'due_date',
                'status',
                'grand_total',
                'settled_total',
                'balance_total',
            ])
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('ledger_direction', $direction)
            ->where('balance_total', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when(isset($filters['party_type'], $filters['party_id']), function (Builder $query) use ($filters): Builder {
                $partyColumn = $filters['party_type'] === 'supplier' ? 'supplier_id' : 'customer_id';

                return $query->where($partyColumn, (int) $filters['party_id']);
            })
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q
                ->where('invoice_number', 'like', "%$search%")
                ->orWhere('external_reference_number', 'like', "%$search%")))
            ->orderBy('due_date')
            ->orderBy('invoice_date')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'document_type' => $row->document_type,
                'ledger_direction' => $row->ledger_direction,
                'party_type' => $row->ledger_direction === 'payable' ? 'supplier' : 'customer',
                'party_id' => (int) ($row->ledger_direction === 'payable' ? $row->supplier_id : $row->customer_id),
                'invoice_date' => $row->invoice_date,
                'due_date' => $row->due_date,
                'status' => $row->status,
                'grand_total' => $this->money($row->grand_total),
                'settled_total' => $this->money($row->settled_total),
                'balance_total' => $this->money($row->balance_total),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): object
    {
        return DB::transaction(function () use ($payload): object {
            $tenantId = $this->support->tenantId();
            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? null);
            $amount = (float) $payload['amount'];
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => ['Payment amount must be positive.']]);
            }
            $this->support->assertTenantRow('payment_methods', (int) $payload['payment_method_id'], 'payment_method_id');
            $partyType = (string) ($payload['party_type'] ?? ($payload['direction'] === 'outbound' ? 'supplier' : 'customer'));
            if ($partyType === 'customer') {
                $this->support->assertTenantRow('customers', (int) $payload['party_id'], 'party_id');
            } elseif ($partyType === 'supplier') {
                $this->support->assertTenantRow('suppliers', (int) $payload['party_id'], 'party_id');
            }

            $paymentMethod = DB::table('payment_methods')->where('tenant_id', $tenantId)->where('id', (int) $payload['payment_method_id'])->first();
            $paymentId = DB::table('payments')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'party_type' => $partyType,
                'party_id' => (int) $payload['party_id'],
                'party_role' => $payload['party_role'] ?? ($payload['direction'] === 'outbound' ? 'payee' : 'payer'),
                'source_module' => $payload['source_module'] ?? 'payment',
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'source_reference' => $payload['source_reference'] ?? null,
                'reference' => $payload['reference'] ?? null,
                'payment_number' => $payload['payment_number'] ?? $this->support->nextNumber('PAY', 'payments', 'payment_number'),
                'payment_date' => $payload['payment_date'],
                'amount' => $amount,
                'allocated_amount' => 0,
                'direction' => $payload['direction'] ?? 'inbound',
                'payment_method_id' => (int) $payload['payment_method_id'],
                'account_id' => $payload['account_id'] ?? $paymentMethod?->account_id,
                'currency_id' => $payload['currency_id'] ?? null,
                'exchange_rate' => $payload['exchange_rate'] ?? 1,
                'base_amount' => $amount,
                'status' => 'posted',
                'notes' => $payload['notes'] ?? null,
                'idempotency_key' => $payload['idempotency_key'] ?? null,
                'created_by' => $this->support->userId(),
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->posting->postPayment($paymentId);
            $this->allocations->allocate($paymentId, array_values($payload['allocations'] ?? []));
            $this->createAdvanceForOverpayment($paymentId);

            return $this->find($paymentId);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     */
    public function allocate(int $paymentId, array $allocations): object
    {
        $this->allocations->allocate($paymentId, $allocations);
        $this->createAdvanceForOverpayment($paymentId);

        return $this->find($paymentId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     */
    public function allocateAdvance(int $advancePaymentId, array $allocations): object
    {
        $this->allocations->allocateAdvance($advancePaymentId, $allocations);

        return $this->findAdvance($advancePaymentId);
    }

    public function findAdvance(int $advancePaymentId): object
    {
        $advance = DB::table('advance_payments')
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->where('id', $advancePaymentId)
            ->first();
        if ($advance === null) {
            abort(404);
        }
        $advance->allocations = DB::table('advance_payment_allocations')
            ->where('tenant_id', $this->support->tenantId())
            ->where('advance_payment_id', $advancePaymentId)
            ->where('status', 'active')
            ->get();

        return $advance;
    }

    public function delete(int $paymentId): void
    {
        DB::transaction(function () use ($paymentId): void {
            $payment = DB::table('payments')->where('tenant_id', $this->support->tenantId())->where('id', $paymentId)->lockForUpdate()->first();
            if ($payment === null) {
                abort(404);
            }
            if ((float) $payment->allocated_amount > 0) {
                throw ValidationException::withMessages(['payment_id' => ['Allocated payments cannot be deleted. Reverse the allocations instead.']]);
            }
            DB::table('payments')->where('id', $paymentId)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    private function createAdvanceForOverpayment(int $paymentId): void
    {
        $payment = DB::table('payments')->where('tenant_id', $this->support->tenantId())->where('id', $paymentId)->first();
        if ($payment === null) {
            return;
        }
        $remaining = round((float) $payment->amount - (float) $payment->allocated_amount, 4);
        if ($remaining <= 0 || DB::table('advance_payments')->where('tenant_id', $payment->tenant_id)->where('payment_id', $paymentId)->exists()) {
            return;
        }

        DB::table('advance_payments')->insert([
            'tenant_id' => (int) $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'party_type' => (string) $payment->party_type,
            'party_id' => (int) $payment->party_id,
            'reference' => $payment->reference,
            'advance_number' => $this->support->nextNumber('ADV', 'advance_payments', 'advance_number'),
            'amount' => $remaining,
            'currency_id' => $payment->currency_id,
            'exchange_rate' => $payment->exchange_rate,
            'base_amount' => $remaining,
            'remaining_amount' => $remaining,
            'advance_date' => $payment->payment_date,
            'type' => $payment->party_type,
            'status' => 'open',
            'payment_id' => $paymentId,
            'notes' => 'Unallocated payment balance.',
            'created_by' => $this->support->userId(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
