import { useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { approvePayment, getPayment, getPaymentAllocations, getPaymentUnappliedBalance, postPayment, refundPayment, reversePayment, submitPayment, voidPayment as voidPaymentAction } from '../paymentApi';
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
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [refundAmount, setRefundAmount] = useState('0.000000');
    const [refundDate, setRefundDate] = useState(today());
    const [refundNumber, setRefundNumber] = useState('');
    const [refundReason, setRefundReason] = useState('');
    const [reversalDate, setReversalDate] = useState(today());
    const [reversalNumber, setReversalNumber] = useState('');
    const [reversalReason, setReversalReason] = useState('');

    if (payment.loading) return <LoadingState />;
    if (!payment.data) return <ErrorAlert error={payment.error} />;

    const value = payment.data;
    const fromPurchase = searchParams.get('from') === 'purchase';
    const chequeLine = value.lines?.find((line) => line.payment_method?.method_type === 'cheque' && line.id) ?? null;
    const capabilities = value.capabilities ?? {};
    const canRefund = Boolean(capabilities.can_refund) && hasPaymentPermission(auth.permissions, paymentPermissions.refund);
    const canReverse = Boolean(capabilities.can_reverse) && hasPaymentPermission(auth.permissions, paymentPermissions.reverse);
    const canSubmit = Boolean(capabilities.can_submit) && hasPaymentPermission(auth.permissions, paymentPermissions.submit);
    const canApprove = Boolean(capabilities.can_approve) && hasPaymentPermission(auth.permissions, paymentPermissions.approve);
    const canPost = Boolean(capabilities.can_post) && hasPaymentPermission(auth.permissions, paymentPermissions.post);
    const canVoid = Boolean(capabilities.can_void) && hasPaymentPermission(auth.permissions, paymentPermissions.void);
    const canPrintCheque = Boolean(capabilities.can_print_cheque) && chequeLine?.id && hasPaymentPermission(auth.permissions, paymentPermissions.chequesPrint);

    async function runPaymentAction(action: 'submit' | 'approve' | 'post' | 'void') {
        if (busy) return;
        setBusy(true);
        setActionError(null);
        try {
            if (action === 'submit') await submitPayment(id);
            if (action === 'approve') await approvePayment(id);
            if (action === 'post') await postPayment(id);
            if (action === 'void') await voidPaymentAction(id, reversalReason || undefined);
            payment.reload();
            allocations.reload();
            unapplied.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function submitRefund() {
        if (busy) return;
        setBusy(true);
        setActionError(null);
        try {
            await refundPayment(id, { refund_number: refundNumber, refund_date: refundDate, amount: refundAmount, reason: refundReason || undefined });
            payment.reload();
            unapplied.reload();
            setRefundAmount('0.000000');
            setRefundNumber('');
            setRefundReason('');
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function submitReversal() {
        if (busy) return;
        setBusy(true);
        setActionError(null);
        try {
            await reversePayment(id, { reversal_number: reversalNumber, reversal_date: reversalDate, reason: reversalReason });
            payment.reload();
            allocations.reload();
            unapplied.reload();
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
                {canPrintCheque && <LinkButton to={`/payments/${id}/lines/${chequeLine?.id}/cheque-print`}>Print cheque</LinkButton>}
            </div>}
        />
        <Panel className="p-0">
            <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
            <div className="p-5">
                <ErrorAlert error={actionError} />
                {tabState.activeTab === 'summary' && <DetailGrid items={[
                    { label: 'Status', value: <StatusBadge status={value.status} /> },
                    { label: 'Posting', value: <StatusBadge status={value.posting_status} /> },
                    { label: 'Allocation', value: <StatusBadge status={value.allocation_status} /> },
                    { label: 'Party', value: readableRelation(value.party) },
                    { label: 'Type', value: humanize(value.payment_type) },
                    { label: 'Direction', value: humanize(value.direction) },
                    { label: 'Total', value: <MoneyDisplay value={value.total_amount} /> },
                    { label: 'Allocated', value: <MoneyDisplay value={value.allocated_amount} /> },
                    { label: 'Unapplied', value: <MoneyDisplay value={value.unapplied_amount} /> },
                    { label: 'Refunded', value: <MoneyDisplay value={value.refunded_amount} /> },
                    { label: 'Finance journal', value: value.finance_journal?.number ?? value.finance_journal?.id ?? '-' },
                    { label: 'Source', value: humanize(value.source_type) },
                ]} />}
                {tabState.activeTab === 'lines' && <RecordTable rows={(value.lines ?? []) as Record<string, unknown>[]} fields={['payment_method', 'amount', 'cleared_amount', 'reference_number', 'status', 'instrument_number', 'instrument_date', 'metadata']} />}
                {tabState.activeTab === 'allocations' && (allocations.loading ? <LoadingState /> : allocations.error ? <ErrorAlert error={allocations.error} /> : <RecordTable rows={allocations.data ?? []} fields={['invoice', 'allocation_date', 'allocated_amount', 'invoice_balance_after', 'allocation_method', 'status']} />)}
                {tabState.activeTab === 'unapplied' && (unapplied.loading ? <LoadingState /> : unapplied.error ? <ErrorAlert error={unapplied.error} /> : <RecordTable rows={unapplied.data ? [unapplied.data] : []} fields={['balance_type', 'original_amount', 'allocated_amount', 'refunded_amount', 'remaining_amount', 'allocation_status', 'status']} />)}
                {tabState.activeTab === 'refunds' && <div className="space-y-5">
                    <RecordTable rows={(value.refunds ?? []) as Record<string, unknown>[]} fields={['refund_number', 'refund_date', 'amount', 'refund_payment', 'reason', 'status']} />
                    {canRefund && <div className="grid gap-4 md:grid-cols-[180px_180px_180px_minmax(0,1fr)_auto] md:items-end">
                        <Input label="Refund number" value={refundNumber} onChange={(event) => setRefundNumber(event.target.value)} />
                        <Input label="Refund date" type="date" value={refundDate} onChange={(event) => setRefundDate(event.target.value)} />
                        <DecimalInput label="Amount" value={refundAmount} onChange={(event) => setRefundAmount(event.target.value)} />
                        <Input label="Reason" value={refundReason} onChange={(event) => setRefundReason(event.target.value)} />
                        <Button loading={busy} onClick={() => void submitRefund()}>Refund</Button>
                    </div>}
                </div>}
                {tabState.activeTab === 'reversals' && <div className="space-y-5">
                    <RecordTable rows={(value.reversals ?? []) as Record<string, unknown>[]} fields={['reversal_number', 'reversal_date', 'original_amount', 'reversed_amount', 'reason', 'status']} />
                    {canReverse && <div className="grid gap-4 md:grid-cols-[180px_180px_minmax(0,1fr)_auto] md:items-end">
                        <Input label="Reversal number" value={reversalNumber} onChange={(event) => setReversalNumber(event.target.value)} />
                        <Input label="Reversal date" type="date" value={reversalDate} onChange={(event) => setReversalDate(event.target.value)} />
                        <Input label="Reason" value={reversalReason} onChange={(event) => setReversalReason(event.target.value)} />
                        <Button variant="danger" loading={busy} onClick={() => void submitReversal()}>Reverse</Button>
                    </div>}
                </div>}
                {tabState.activeTab === 'history' && <RecordTable rows={(value.status_history ?? []) as Record<string, unknown>[]} fields={['changed_at', 'from_status', 'to_status', 'reason']} />}
            </div>
        </Panel>
    </>;
}