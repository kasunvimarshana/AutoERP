import { useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import {
    approvePayment,
    getPayment,
    getPaymentAllocations,
    getPaymentUnappliedBalance,
    postPayment,
    refundPayment,
    reversePayment,
    submitPayment,
    voidPayment as voidPaymentAction,
} from '../paymentApi';
import { hasPaymentPermission, paymentPermissions } from '../paymentPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { RecordTable } from '@/shared/components/RecordTable';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { humanize, readableRelation } from '@/shared/utils/object';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { useDetailResourceStore } from '@/shared/state/useDetailResourceStore';

type Tab = 'summary' | 'lines' | 'allocations' | 'unapplied' | 'refunds' | 'reversals' | 'history';
const tabs = [
    { id: 'summary' as Tab, label: 'Summary' },
    { id: 'lines' as Tab, label: 'Methods' },
    { id: 'allocations' as Tab, label: 'Allocations' },
    { id: 'unapplied' as Tab, label: 'Unapplied' },
    { id: 'refunds' as Tab, label: 'Refunds' },
    { id: 'reversals' as Tab, label: 'Reversals' },
    { id: 'history' as Tab, label: 'History' },
];

const today = () => businessDateInputValue();

export default function PaymentDetailPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const [searchParams] = useSearchParams();
    const tabState = useOnDemandTab<Tab>('summary');
    const payment = useApi((signal) => getPayment(id, signal), [id]);
    const allocations = useApi((signal) => getPaymentAllocations(id, signal), [id], tabState.openedTabs.has('allocations'));
    const unapplied = useApi((signal) => getPaymentUnappliedBalance(id, signal), [id], tabState.openedTabs.has('unapplied'));
    const paymentState = useDetailResourceStore(payment.data);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [refundAmount, setRefundAmount] = useState('0.000000');
    const [refundDate, setRefundDate] = useState(today());
    const [refundReason, setRefundReason] = useState('');
    const [reversalDate, setReversalDate] = useState(today());
    const [reversalReason, setReversalReason] = useState('');
    const [voidReason, setVoidReason] = useState('');

    if (payment.loading && paymentState.data === null) return <LoadingState />;
    if (!paymentState.data) return <ErrorAlert error={payment.error} />;

    const value = paymentState.data;
    const fromPurchase = searchParams.get('from') === 'purchase';
    const chequeLine = value.lines?.find((line) => line.payment_method?.method_type === 'cheque') ?? null;
    const capabilities = value.capabilities ?? {};
    const canRefund = Boolean(capabilities.can_refund) && hasPaymentPermission(auth, paymentPermissions.refund);
    const canReverse = Boolean(capabilities.can_reverse) && hasPaymentPermission(auth, paymentPermissions.reverse);
    const canSubmit = Boolean(capabilities.can_submit) && hasPaymentPermission(auth, paymentPermissions.submit);
    const canApprove = Boolean(capabilities.can_approve) && hasPaymentPermission(auth, paymentPermissions.approve);
    const canPost = Boolean(capabilities.can_post) && hasPaymentPermission(auth, paymentPermissions.post);
    const canVoid = Boolean(capabilities.can_void) && hasPaymentPermission(auth, paymentPermissions.void);
    const canPrintCheque = Boolean(capabilities.can_print_cheque) && chequeLine?.id && hasPaymentPermission(auth, paymentPermissions.chequesPrint);
    const refundValid = isPositiveDecimal(refundAmount) && refundReason.trim() !== '';
    const reversalValid = reversalReason.trim() !== '';

    async function refreshPaymentState() {
        const refreshed = await getPayment(id);
        paymentState.setData(refreshed);

        if (tabState.openedTabs.has('allocations')) {
            allocations.reload();
        }
        if (tabState.openedTabs.has('unapplied')) {
            unapplied.reload();
        }
    }

    async function runPaymentAction(action: 'submit' | 'approve' | 'post' | 'void') {
        if (busy) return;
        setBusy(true);
        setActionError(null);
        try {
            if (action === 'submit') paymentState.setData(await submitPayment(id, value.row_version));
            if (action === 'approve') paymentState.setData(await approvePayment(id, value.row_version));
            if (action === 'post') paymentState.setData(await postPayment(id, value.row_version));
            if (action === 'void') paymentState.setData(await voidPaymentAction(id, value.row_version, voidReason.trim() || undefined));
            if (tabState.openedTabs.has('allocations')) {
                allocations.reload();
            }
            if (tabState.openedTabs.has('unapplied')) {
                unapplied.reload();
            }
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function submitRefund() {
        if (busy || !refundValid) return;
        setBusy(true);
        setActionError(null);
        try {
            await refundPayment(id, {
                expected_version: value.row_version,
                refund_date: refundDate,
                amount: refundAmount,
                reason: refundReason.trim(),
            });
            await refreshPaymentState();
            setRefundAmount('0.000000');
            setRefundReason('');
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function submitReversal() {
        if (busy || !reversalValid) return;
        setBusy(true);
        setActionError(null);
        try {
            await reversePayment(id, {
                expected_version: value.row_version,
                reversal_date: reversalDate,
                reason: reversalReason.trim(),
            });
            await refreshPaymentState();
            setReversalReason('');
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    return <>
        <ContentHeader
            title={value.payment_number ?? 'Payment'}
            description={formatDate(value.payment_date)}
            actions={<div className="flex flex-wrap justify-end gap-2">
                {fromPurchase && <LinkButton to="/purchase/payments" variant="secondary">Back to Purchase</LinkButton>}
                {canSubmit && <Button variant="secondary" loading={busy} onClick={() => void runPaymentAction('submit')}>Submit</Button>}
                {canApprove && <Button variant="secondary" loading={busy} onClick={() => void runPaymentAction('approve')}>Approve</Button>}
                {canPost && <Button loading={busy} onClick={() => void runPaymentAction('post')}>Post</Button>}
                {canVoid && <Button variant="danger" loading={busy} onClick={() => void runPaymentAction('void')}>Void</Button>}
                {canPrintCheque && <LinkButton to={`/payments/${id}/lines/${chequeLine.id}/cheque-print`}>Print cheque</LinkButton>}
            </div>}
        />
        <Panel className="p-0">
            <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
            <div className="p-5">
                <ErrorAlert error={actionError} />
                {canVoid && <div className="mb-4 max-w-xl"><Input label="Void reason" value={voidReason} onChange={(event) => setVoidReason(event.target.value)} /></div>}
                {tabState.activeTab === 'summary' && <DetailGrid items={[
                    { label: 'Document', value: <StatusBadge status={value.document_status} /> },
                    { label: 'Posting', value: <StatusBadge status={value.posting_status} /> },
                    { label: 'Allocation', value: <StatusBadge status={value.allocation_status} /> },
                    { label: 'Instrument', value: <StatusBadge status={value.instrument_status} /> },
                    { label: 'Party', value: readableRelation(value.party) },
                    { label: 'Type', value: humanize(value.payment_type) },
                    { label: 'Direction', value: humanize(value.direction) },
                    { label: 'Total', value: <MoneyDisplay value={value.total_amount} /> },
                    { label: 'Allocated', value: <MoneyDisplay value={value.allocated_amount} /> },
                    { label: 'Unapplied', value: <MoneyDisplay value={value.unapplied_amount} /> },
                    { label: 'Refunded', value: <MoneyDisplay value={value.refunded_amount} /> },
                    { label: 'Finance posting', value: value.finance_posting_reference ?? '-' },
                    { label: 'Source', value: humanize(value.source_type) },
                ]} />}
                {tabState.activeTab === 'lines' && <RecordTable rows={(value.lines ?? []) as unknown as Record<string, unknown>[]} fields={['payment_method', 'amount', 'cleared_amount', 'reference_number', 'status', 'instrument_number', 'instrument_date', 'external_bank_name']} rowKey={(row, index) => String(row.id ?? row.reference_number ?? row.instrument_number ?? `payment-line-${index}`)} />}
                {tabState.activeTab === 'allocations' && (allocations.loading ? <LoadingState /> : allocations.error ? <ErrorAlert error={allocations.error} /> : <RecordTable rows={allocations.data ?? []} fields={['invoice', 'allocation_date', 'allocated_amount', 'invoice_balance_after', 'allocation_method', 'status']} rowKey={(row, index) => String(row.id ?? `${String(row.invoice ?? 'invoice')}-${String(row.allocation_date ?? index)}`)} />)}
                {tabState.activeTab === 'unapplied' && (unapplied.loading ? <LoadingState /> : unapplied.error ? <ErrorAlert error={unapplied.error} /> : <RecordTable rows={unapplied.data ? [unapplied.data] : []} fields={['balance_type', 'original_amount', 'allocated_amount', 'refunded_amount', 'remaining_amount', 'allocation_status', 'status']} rowKey={() => 'unapplied-balance'} />)}
                {tabState.activeTab === 'refunds' && <div className="space-y-5">
                    <RecordTable rows={(value.refunds ?? []) as Record<string, unknown>[]} fields={['refund_number', 'refund_date', 'amount', 'refund_payment', 'reason', 'status']} rowKey={(row, index) => String(row.id ?? row.refund_number ?? `refund-${index}`)} />
                    {canRefund && <div className="grid gap-4 md:grid-cols-[180px_180px_minmax(0,1fr)_auto] md:items-end">
                        <Input label="Refund date" type="date" value={refundDate} onChange={(event) => setRefundDate(event.target.value)} />
                        <DecimalInput label="Amount" value={refundAmount} onChange={(event) => setRefundAmount(event.target.value)} />
                        <Input label="Reason *" value={refundReason} onChange={(event) => setRefundReason(event.target.value)} />
                        <Button loading={busy} disabled={!refundValid} onClick={() => void submitRefund()}>Refund</Button>
                    </div>}
                </div>}
                {tabState.activeTab === 'reversals' && <div className="space-y-5">
                    <RecordTable rows={(value.reversals ?? []) as Record<string, unknown>[]} fields={['reversal_number', 'reversal_date', 'original_amount', 'reversed_amount', 'reason', 'status']} rowKey={(row, index) => String(row.id ?? row.reversal_number ?? `reversal-${index}`)} />
                    {canReverse && <div className="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)_auto] md:items-end">
                        <Input label="Reversal date" type="date" value={reversalDate} onChange={(event) => setReversalDate(event.target.value)} />
                        <Input label="Reason *" value={reversalReason} onChange={(event) => setReversalReason(event.target.value)} />
                        <Button variant="danger" loading={busy} disabled={!reversalValid} onClick={() => void submitReversal()}>Reverse</Button>
                    </div>}
                </div>}
                {tabState.activeTab === 'history' && <RecordTable rows={(value.lifecycle_events ?? []) as Record<string, unknown>[]} fields={['occurred_at', 'event_type', 'from_document_status', 'to_document_status', 'reason']} rowKey={(row, index) => String(row.id ?? row.occurred_at ?? `lifecycle-${index}`)} />}
            </div>
        </Panel>
    </>;
}
