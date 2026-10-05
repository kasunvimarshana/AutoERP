import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { humanize } from '@/shared/utils/object';
import { settlePaymentLine, type Payment, type PaymentLine } from '../paymentApi';

interface Draft {
    status: string;
    eventDate: string;
    reason: string;
}

export function PaymentSettlementPanel({
    payment,
    enabled,
    onChanged,
}: {
    payment: Payment;
    enabled: boolean;
    onChanged: () => Promise<void>;
}) {
    const [drafts, setDrafts] = useState<Record<number, Draft>>({});
    const [busyLine, setBusyLine] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const actionable = (payment.lines ?? []).filter((line) => (line.allowed_settlement_statuses?.length ?? 0) > 0);

    if (!enabled || actionable.length === 0) return null;

    function draft(line: PaymentLine): Draft {
        return drafts[line.id] ?? { status: '', eventDate: businessDateInputValue(), reason: '' };
    }

    function patch(line: PaymentLine, values: Partial<Draft>) {
        setDrafts((current) => ({ ...current, [line.id]: { ...draft(line), ...values } }));
    }

    async function settle(line: PaymentLine) {
        const value = draft(line);
        if (!value.status || !value.eventDate || busyLine !== null) return;
        setBusyLine(line.id);
        setError(null);
        try {
            await settlePaymentLine(payment.id, line.id, {
                expected_payment_version: payment.row_version,
                expected_line_version: line.row_version,
                status: value.status,
                event_date: value.eventDate,
                reason: value.reason.trim() || undefined,
            });
            setDrafts((current) => {
                const next = { ...current };
                delete next[line.id];
                return next;
            });
            await onChanged();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusyLine(null);
        }
    }

    return (
        <div className="mt-5 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div>
                <h3 className="font-semibold text-slate-900">Settle instruments</h3>
                <p className="mt-1 text-sm text-slate-600">Record the real business date when a cheque, transfer, card, wallet, or cash line changes state.</p>
            </div>
            <ErrorAlert error={error} inline />
            {actionable.map((line) => {
                const value = draft(line);
                return (
                    <div key={line.id} className="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 lg:grid-cols-[minmax(0,1fr)_180px_180px_minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">{line.payment_method?.name ?? `Payment line ${line.line_number}`}</div>
                            <div className="mt-1"><StatusBadge status={line.status} /></div>
                        </div>
                        <Select
                            label="Next state"
                            value={value.status}
                            onChange={(event) => patch(line, { status: event.target.value })}
                            options={[
                                { value: '', label: 'Select state' },
                                ...(line.allowed_settlement_statuses ?? []).map((status) => ({ value: status, label: humanize(status) })),
                            ]}
                        />
                        <Input label="Event date" type="date" value={value.eventDate} onChange={(event) => patch(line, { eventDate: event.target.value })} />
                        <Input label="Reason / reference note" value={value.reason} onChange={(event) => patch(line, { reason: event.target.value })} />
                        <Button disabled={!value.status || !value.eventDate} loading={busyLine === line.id} onClick={() => void settle(line)}>Record</Button>
                    </div>
                );
            })}
        </div>
    );
}
