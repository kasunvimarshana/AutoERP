import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import {
    listUsablePaymentMethods,
    refundPayment,
    type Payment,
} from '../paymentApi';
import {
    lineIsValid,
    linePayload,
    paymentMethodKind,
} from '../paymentLineInput';
import { PaymentMethodFields } from './PaymentMethodFields';
import type { PaymentLineDraft } from './PaymentLineTable';

const REFUND_DIRECTION_INBOUND = 'inbound';
const REFUND_DIRECTION_OUTBOUND = 'outbound';
const PAGE_SIZE = 100;

function refundDirection(payment: Payment): string {
    return payment.direction === REFUND_DIRECTION_INBOUND
        ? REFUND_DIRECTION_OUTBOUND
        : REFUND_DIRECTION_INBOUND;
}

export function PaymentRefundPanel({
    payment,
    enabled,
    onChanged,
}: {
    payment: Payment;
    enabled: boolean;
    onChanged: () => Promise<void>;
}) {
    const direction = refundDirection(payment);
    const [refundDate, setRefundDate] = useState(businessDateInputValue());
    const [amount, setAmount] = useState('0.000000');
    const [reason, setReason] = useState('');
    const [exchangeRate, setExchangeRate] = useState(payment.currency?.id ? '' : '1.000000');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [reference, setReference] = useState('');
    const [metadata, setMetadata] = useState<Record<string, string>>({});
    const [createdRefundId, setCreatedRefundId] = useState<number | null>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const methods = useApi(
        (signal) => listUsablePaymentMethods({ direction, per_page: PAGE_SIZE }, signal),
        [direction],
        enabled,
    );
    const methodRows = methods.data?.data ?? [];
    const method = useMemo(
        () => methodRows.find((candidate) => String(candidate.id) === paymentMethodId),
        [methodRows, paymentMethodId],
    );
    const line: PaymentLineDraft = {
        key: 1,
        paymentMethodId,
        amount,
        reference,
        metadata,
    };
    const valid = enabled
        && refundDate !== ''
        && reason.trim() !== ''
        && isPositiveDecimal(exchangeRate)
        && lineIsValid(line, method, '', direction);

    async function submit() {
        if (!valid || !method || busy) return;
        setBusy(true);
        setError(null);
        setCreatedRefundId(null);

        try {
            const methodPayload = linePayload(line, method, direction);
            const result = await refundPayment(payment.id, {
                expected_version: payment.row_version,
                refund_date: refundDate,
                amount,
                reason: reason.trim(),
                payment_method_id: method.id,
                exchange_rate: exchangeRate,
                reference_number: methodPayload.reference_number,
                external_bank_name: methodPayload.external_bank_name,
                external_bank_branch: methodPayload.external_bank_branch,
                instrument_number: methodPayload.instrument_number,
                instrument_date: methodPayload.instrument_date,
            });
            const refundPayment = result.refund_payment;
            if (refundPayment && typeof refundPayment === 'object' && 'id' in refundPayment) {
                const id = Number((refundPayment as { id?: unknown }).id);
                if (Number.isInteger(id) && id > 0) setCreatedRefundId(id);
            }
            await onChanged();
            setAmount('0.000000');
            setReason('');
            setPaymentMethodId('');
            setReference('');
            setMetadata({});
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (!enabled) return null;

    return (
        <div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 className="font-semibold text-slate-900">Create refund draft</h3>
            <p className="mt-1 text-sm text-slate-600">
                Record the refund using the actual refund-date exchange rate and payment method.
                The refund has no financial effect until it is submitted, approved and posted through the normal Payment workflow.
            </p>
            <ErrorAlert error={error ?? methods.error} inline />
            {createdRefundId !== null && (
                <p role="status" className="mt-3 text-sm text-emerald-700">
                    Refund draft created. <Link className="underline" to={`/payments/${createdRefundId}`}>Review refund payment</Link>
                </p>
            )}
            <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <Input label="Refund date" type="date" min={payment.payment_date ?? undefined} value={refundDate} disabled={busy} onChange={(event) => setRefundDate(event.target.value)} />
                <DecimalInput label="Amount" value={amount} disabled={busy} onChange={(event) => setAmount(event.target.value)} />
                <DecimalInput label="Exchange rate to base currency" value={exchangeRate} disabled={busy} onChange={(event) => setExchangeRate(event.target.value)} />
                <Select
                    label="Refund method"
                    value={paymentMethodId}
                    disabled={busy || methods.loading}
                    options={methodRows.map((row) => ({ value: String(row.id), label: row.name }))}
                    placeholder={methods.loading ? 'Loading methods...' : 'Configured method'}
                    onChange={(event) => {
                        setPaymentMethodId(event.target.value);
                        setMetadata({});
                    }}
                />
                <Input label="Reference" value={reference} disabled={busy} onChange={(event) => setReference(event.target.value)} />
            </div>
            <PaymentMethodFields
                kind={paymentMethodKind(method)}
                metadata={metadata}
                disabled={busy}
                onChange={(field, value) => setMetadata((current) => ({ ...current, [field]: value }))}
            />
            <div className="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <Input label="Reason *" value={reason} disabled={busy} onChange={(event) => setReason(event.target.value)} />
                <Button loading={busy} disabled={!valid} onClick={() => void submit()}>Create refund draft</Button>
            </div>
        </div>
    );
}
