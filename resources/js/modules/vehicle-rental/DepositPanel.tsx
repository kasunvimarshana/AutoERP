import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { listPaymentMethods } from '@/modules/payment/paymentApi';
import { PaymentLineTable, type PaymentLineDraft } from '@/modules/payment/components/PaymentLineTable';
import { linePayload, lineIsValid } from '@/modules/payment/paymentLineInput';
import { useApi } from '@/shared/hooks/useApi';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { compareDecimalStrings, isPositiveDecimal, sumDecimals } from '@/shared/utils/decimal';
import { AgreementStatus, type Agreement } from './agreements';
import { getDepositSummary, receiveDeposit, type DepositSummary } from './depositApi';
const DIRECTION = 'inbound';
const FIRST_LINE = 1;
const METHOD_PAGE_SIZE = 100;
const emptyLine = (key: number): PaymentLineDraft => ({ key, paymentMethodId: '', amount: '', reference: '', metadata: {} });
export function DepositPanel({ agreement, canCreate }: { agreement: Agreement; canCreate: boolean }) {
    const [summary, setSummary] = useState<DepositSummary | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [revision, setRevision] = useState(0);
    const [busy, setBusy] = useState(false);
    const [date, setDate] = useState(businessDateInputValue);
    const [rate, setRate] = useState('');
    const [lines, setLines] = useState<PaymentLineDraft[]>([emptyLine(FIRST_LINE)]);
    const nextLine = useRef(FIRST_LINE + 1);
    const inFlight = useRef(false);
    const requests = useRef(new Map<string, string>());
    const methods = useApi(signal => listPaymentMethods({ direction: DIRECTION, per_page: METHOD_PAGE_SIZE }, signal), [], canCreate);
    useEffect(() => {
        const controller = new AbortController();
        getDepositSummary(agreement.id, controller.signal).then(value => { if (!controller.signal.aborted) setSummary(value); })
            .catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); });
        return () => controller.abort();
    }, [agreement.id, revision]);
    const rows = methods.data?.data ?? [];
    const total = sumDecimals(lines.map(line => line.amount || '0'));
    const valid = summary !== null && summary.remaining_to_receive !== null && isPositiveDecimal(total) && isPositiveDecimal(rate)
        && compareDecimalStrings(total, summary.remaining_to_receive) <= 0 && lines.length > 0
        && lines.every(line => lineIsValid(line, rows.find(method => String(method.id) === line.paymentMethodId), '', DIRECTION));
    async function receive() {
        if (!valid || inFlight.current) return;
        inFlight.current = true; setBusy(true); setError(null);
        try {
            const input = { expected_version: agreement.row_version, payment_date: date, exchange_rate: rate,
                lines: lines.map(line => linePayload(line, rows.find(method => String(method.id) === line.paymentMethodId)!, DIRECTION)) };
            const signature = JSON.stringify(input);
            const key = requests.current.get(signature) ?? crypto.randomUUID();
            requests.current.set(signature, key);
            await receiveDeposit(agreement.id, input, key);
            requests.current.delete(signature);
            setLines([emptyLine(nextLine.current++)]);
            setRevision(value => value + 1);
        } catch (failure) { setError(toApiError(failure)); }
        finally { inFlight.current = false; setBusy(false); }
    }
    return <section className="space-y-3 border-t pt-4" aria-label="Security deposit">
        <h3 className="font-semibold">Security deposit · {agreement.currency.code}</h3>
        <p>Receive the agreed deposit, then open its payment to approve, post, apply to an invoice or refund. Draft receipts reserve collection capacity; they are not posted cash.</p>
        <ErrorAlert error={error ?? methods.error} inline />
        <Button variant="secondary" disabled={busy} onClick={() => setRevision(value => value + 1)}>Refresh deposits</Button>
        {summary && <>
            <p>Agreed: {summary.requirement ?? 'Not specified'} · Net receipts including drafts: {summary.net_receipts} · Remaining collection capacity: {summary.remaining_to_receive ?? 'Not specified'}</p>
            {canCreate && agreement.status === AgreementStatus.Active && isPositiveDecimal(summary.remaining_to_receive ?? '0') && <fieldset disabled={busy} className="space-y-3">
                <Input label="Deposit receipt date" type="date" value={date} onChange={event => setDate(event.target.value)} required />
                <Input label="Deposit exchange rate" value={rate} onChange={event => setRate(event.target.value)} required />
                <PaymentLineTable lines={lines} methods={rows} methodsLoading={methods.loading} total={total}
                    onLineChange={(key, patch) => setLines(current => current.map(line => line.key === key ? { ...line, ...patch } : line))}
                    onMetadataChange={(key, field, value) => setLines(current => current.map(line => line.key === key ? { ...line, metadata: { ...line.metadata, [field]: value } } : line))}
                    onAddLine={() => setLines(current => [...current, emptyLine(nextLine.current++)])}
                    onRemoveLine={key => setLines(current => current.filter(line => line.key !== key))} />
                <Button onClick={receive} disabled={!valid} loading={busy}>Create deposit receipt</Button>
            </fieldset>}
            <ul>{summary.payments.map(payment => <li key={payment.id}>
                <Link to={`/payments/${payment.id}`} className="text-blue-700 underline">{payment.payment_number}</Link>
                {' · '}{payment.document_status} / {payment.posting_status} · Received {payment.total_amount} · Applied {payment.allocated_amount} · Refunded {payment.refunded_amount} · Unapplied {payment.unapplied_amount}
            </li>)}</ul>
        </>}
    </section>;
}
