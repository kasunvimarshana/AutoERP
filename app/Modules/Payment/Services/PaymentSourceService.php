<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Models\Payment;

final class PaymentSourceService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** The source owner authorizes access before invoking this scoped read. */
    public function documents(int $tenantId, int $organizationUnitId, PaymentSourceType $source, int $sourceId): Collection
    {
        return Payment::query()->where('tenant_id', $tenantId)->where('organization_unit_id', $organizationUnitId)
            ->where('source_type', $source->value)->where('source_id', $sourceId)->with('currency')->orderBy('id')->get();
    }

    /** Unposted drafts reserve capacity; allocations do not authorize a second collection. */
    public function netReceipts(Collection $payments): string
    {
        $amount = $this->math->normalize('0');
        foreach ($payments as $payment) {
            if (in_array($payment->document_status, [PaymentDocumentStatus::Voided, PaymentDocumentStatus::Reversed], true)) {
                continue;
            }
            $amount = $this->math->add($amount, $this->math->sub((string) $payment->total_amount, (string) $payment->refunded_amount));
        }

        return $amount;
    }
}
