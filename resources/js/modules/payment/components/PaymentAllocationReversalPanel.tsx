import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { reversePaymentAllocation, type Payment, type PaymentAllocation } from '../paymentApi';

export function PaymentAllocationReversalPanel({
    payment,
    allocations,
    enabled,
    onChanged,
}: {
    payment: Payment;
    allocations: PaymentAllocation[];
    enabled: boolean;
    onChanged: () => Promise<void>;
}) {
    const active = useMemo(
        () => allocations.filter((allocation) => allocation.status === 'active'),
        [allocations],
    );
    const [allocationId, setAllocationId] = useState('');
    const [reversalDate, setReversalDate] = useState(businessDateInputValue());
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const selected = active.find((allocation) => String(allocation.id) === allocationId) ?? null;
    const valid = enabled && selected !== null && reversalDate !== '' && reason.trim() !== '';

    async function reverse() {
        if (!valid || !selected || busy) return;
        setBusy(true);
        setError(null);
        try {
            await reversePaymentAllocation(payment.id, selected.id, {
                expected_payment_version: payment.row_version,
                expected_allocation_version: selected.row_version,
                reversal_date: reversalDate,
                reason: reason.trim(),
            });
            setAllocationId('');
            setReason('');
            await onChanged();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (!enabled || active.length === 0) return null;

    return (
        <div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 className="font-semibold text-slate-900">Reverse one allocation</h3>
            <p className="mt-1 text-sm text-slate-600">
                Reverse only the selected invoice application and its Finance reclassification.
                The original Payment remains posted and the amount returns to unapplied balance.
            </p>
            <ErrorAlert error={error} inline />
            <div className="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_minmax(0,1fr)_auto] md:items-end">
                <Select
                    label="Allocation"
                    value={allocationId}
                    disabled={busy}
                    options={active.map((allocation) => ({
                        value: String(allocation.id),
                        label: `${allocation.invoice?.invoice_number ?? allocation.invoice?.name ?? `Invoice ${allocation.invoice_id}`} · ${allocation.allocated_amount}`,
                    }))}
                    onChange={(event) => setAllocationId(event.target.value)}
                />
                <Input
                    label="Reversal date"
                    type="date"
                    min={selected?.allocation_date ?? payment.payment_date ?? undefined}
                    value={reversalDate}
                    disabled={busy}
                    onChange={(event) => setReversalDate(event.target.value)}
                />
                <Input label="Reason *" value={reason} disabled={busy} onChange={(event) => setReason(event.target.value)} />
                <Button variant="danger" loading={busy} disabled={!valid} onClick={() => void reverse()}>Reverse allocation</Button>
            </div>
        </div>
    );
}
