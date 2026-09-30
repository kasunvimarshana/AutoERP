<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Invoice\Contracts\InvoicePaymentMethodProviderInterface;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Models\PaymentAllocation;

final class InvoicePaymentMethodProvider implements InvoicePaymentMethodProviderInterface
{
    public function __construct(
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function namesForInvoice(
        int $invoiceId,
        int $tenantId,
        ?int $organizationUnitId,
    ): array {
        return $this->executionContext->runForTenant(
            $tenantId,
            fn (): array => $this->scopedNames($invoiceId, $tenantId, $organizationUnitId),
        );
    }

    /** @return list<string> */
    private function scopedNames(int $invoiceId, int $tenantId, ?int $organizationUnitId): array
    {
        $query = PaymentAllocation::query()
            ->with(['payment.lines' => static fn ($query) => $query->orderBy('line_number')])
            ->where('invoice_id', $invoiceId)
            ->where('tenant_id', $tenantId)
            ->where('status', AllocationStatus::Active->value)
            ->orderBy('payment_id');

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        return $query->get()
            ->flatMap(static fn (PaymentAllocation $allocation) => $allocation->payment?->lines ?? collect())
            ->pluck('payment_method_name_snapshot')
            ->map(static fn (mixed $name): string => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
