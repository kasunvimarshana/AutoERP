import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { AttachmentPanel } from '../../../shared/components/business/AttachmentPanel';
import { CommentPanel } from '../../../shared/components/business/CommentPanel';
import {
    PaymentActivityTimeline,
    PaymentAllocationPanel,
    PaymentPostingPreviewPanel,
    PaymentSourceReferencePanel,
    PaymentSummaryCard,
    RefundPanel,
    WriteOffPanel,
} from '../components/PaymentComponents';
import { paymentApi } from '../services/paymentApi';
import type { Payment, PaymentAllocation, PaymentAuditEntry, PaymentPostingPreview, PaymentSourceReference, Refund, WriteOff } from '../types/payment.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Allocations', value: 'allocations' },
    { label: 'Source References', value: 'source' },
    { label: 'Finance / Posting', value: 'posting' },
    { label: 'Refunds', value: 'refunds' },
    { label: 'Write-offs', value: 'writeoffs' },
    { label: 'Attachments', value: 'attachments' },
    { label: 'Comments', value: 'comments' },
    { label: 'Audit / History', value: 'audit' },
];

export function PaymentDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const [payment, setPayment] = useState<Payment | null>(null);
    const [activity, setActivity] = useState<PaymentAuditEntry[]>([]);
    const [allocations, setAllocations] = useState<PaymentAllocation[]>([]);
    const [references, setReferences] = useState<PaymentSourceReference[]>([]);
    const [postingPreview, setPostingPreview] = useState<PaymentPostingPreview | null>(null);
    const [refunds, setRefunds] = useState<Refund[]>([]);
    const [writeOffs, setWriteOffs] = useState<WriteOff[]>([]);

    function load() {
        const paymentId = id ?? 'pay-001';
        paymentApi.getPayment(paymentId).then((response) => setPayment(response.data));
        paymentApi.getPaymentActivity(paymentId).then((response) => setActivity(response.data));
        paymentApi.listAllocations().then((response) => setAllocations(response.data.filter((allocation) => allocation.paymentId === paymentId)));
        paymentApi.listSourceReferences(paymentId).then((response) => setReferences(response.data));
        paymentApi.getPaymentPostingPreview(paymentId).then((response) => setPostingPreview(response.data));
        paymentApi.listRefunds().then((response) => setRefunds(response.data));
        paymentApi.listWriteOffs().then((response) => setWriteOffs(response.data));
    }

    useEffect(() => {
        load();
    }, [id]);

    if (!payment) {
        return <PreviewPanel status="Loading" title="Loading payment" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/payments/payments"><Button variant="secondary">Back</Button></Link><Link to={`/payments/payments/${payment.id}/edit`}><Button>Edit Payment</Button></Link></>} eyebrow="Payment" subtitle="Payment detail shows generic settlement context without calculating balances in the frontend." title={payment.paymentNumber} />
            <PaymentSummaryCard onChanged={load} payment={payment} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <Overview payment={payment} /> : null}
            {active === 'allocations' ? <PaymentAllocationPanel allocations={allocations} payment={payment} /> : null}
            {active === 'source' ? <PaymentSourceReferencePanel references={references} /> : null}
            {active === 'posting' && postingPreview ? <PaymentPostingPreviewPanel preview={postingPreview} /> : null}
            {active === 'refunds' ? <RefundPanel refunds={refunds} /> : null}
            {active === 'writeoffs' ? <WriteOffPanel writeOffs={writeOffs} /> : null}
            {active === 'attachments' ? <AttachmentPanel /> : null}
            {active === 'comments' ? <CommentPanel /> : null}
            {active === 'audit' ? <PaymentActivityTimeline entries={activity} /> : null}
        </div>
    );
}

function Overview({ payment }: { payment: Payment }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Party', value: payment.party },
                { label: 'Direction', value: payment.direction.replaceAll('_', ' ') },
                { label: 'Method', value: payment.methodName },
                { label: 'Reference', value: payment.reference ?? 'None' },
                { label: 'Source', value: payment.sourceReference ?? 'No source reference' },
            ]}
            status={payment.status}
            subtitle="Values are displayed from backend response. No settlement math is performed here."
            title="Payment Overview"
        />
    );
}
