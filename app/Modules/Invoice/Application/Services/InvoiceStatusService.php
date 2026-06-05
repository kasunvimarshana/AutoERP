<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class InvoiceStatusService
{
    public function __construct(private readonly FinancialServiceSupport $support) {}

    public function record(int $invoiceId, ?string $from, string $to, string $action, ?string $reason = null): void
    {
        DB::table('invoice_status_histories')->insert([
            'invoice_id' => $invoiceId,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'changed_by' => $this->support->userId(),
            'changed_at' => now(),
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function statusForBalance(float $grandTotal, float $settledTotal, string $documentType): string
    {
        if ($documentType === 'credit_adjustment') {
            return 'credited';
        }
        if ($settledTotal <= 0) {
            return 'issued';
        }
        if ($settledTotal + 0.0001 >= $grandTotal) {
            return 'paid';
        }

        return 'partially_paid';
    }
}
