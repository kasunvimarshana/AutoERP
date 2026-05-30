import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    CustomerAdvancePanel,
    CustomerRefundPanel,
    SalesActivityTimeline,
    SalesFinancePostingPanel,
    SalesPaymentAllocationPanel,
    SalesPaymentForm,
    SalesPaymentTable,
    SalesSourceReferencePanel,
} from '../components/SalesComponents';
import { customerAdvances, customerRefunds, financePostingPreview, salesActivity } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { SalesPayment } from '../types/sales.types';

export function SalesPaymentListPage() {
    const [rows, setRows] = useState<SalesPayment[]>([]);

    useEffect(() => {
        salesApi.payments.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/payments/new"><Button>Create Customer Payment</Button></Link>} eyebrow="Sales" subtitle="Sales-scoped customer payment workspace backed by the generic Payment module." title="Customer Payments" />
            <SearchFilterBar placeholder="Search payment number, customer, method, invoice..." />
            {rows.length ? <SalesPaymentTable rows={rows} /> : <EmptyState description="No payments returned yet." title="No payments" />}
        </div>
    );
}

export function SalesPaymentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Customer Payment" subtitle="Collect customer receipt input and request backend allocation preview before posting." title="Create Customer Payment" />
            <SalesPaymentForm />
        </div>
    );
}

export function SalesPaymentDetailPage() {
    const { id = 'spay-001' } = useParams();
    const [payment, setPayment] = useState<SalesPayment>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.payments.get(id).then((response) => setPayment(response.data));
    }, [id]);

    if (!payment) {
        return <EmptyState description="Loading customer payment details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Backend Actions</Button>} eyebrow="Customer Payment" subtitle="Payment detail with allocation, source invoice, finance posting, and history." title={payment.paymentNumber} />
            <Tabs active={activeTab} items={[{ label: 'Overview', value: 'overview' }, { label: 'Allocations', value: 'allocations' }, { label: 'Source Invoice', value: 'source' }, { label: 'Finance Posting', value: 'finance' }, { label: 'History / Audit', value: 'history' }]} onChange={setActiveTab} />
            {activeTab === 'overview' ? <SalesPaymentTable rows={[payment]} /> : null}
            {activeTab === 'allocations' ? <SalesPaymentAllocationPanel allocations={payment.allocations} /> : null}
            {activeTab === 'source' ? <SalesSourceReferencePanel reference={payment.reference} /> : null}
            {activeTab === 'finance' ? <SalesFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={salesActivity} /> : null}
        </div>
    );
}

export function CustomerAdvanceListPage() {
    return (
        <div className="space-y-6">
            <PageHeader actions={<Button>Create Advance</Button>} eyebrow="Sales" subtitle="Customer advances and later allocations. Remaining balance is backend-owned." title="Customer Advances" />
            <SearchFilterBar placeholder="Search customer advances..." />
            <CustomerAdvancePanel advances={customerAdvances} />
        </div>
    );
}

export function CustomerRefundListPage() {
    return (
        <div className="space-y-6">
            <PageHeader actions={<Button>Create Refund</Button>} eyebrow="Sales" subtitle="Customer refunds linked to sales returns or credits. Refund amount and posting are backend-owned." title="Customer Refunds" />
            <SearchFilterBar placeholder="Search customer refunds..." />
            <CustomerRefundPanel refunds={customerRefunds} />
        </div>
    );
}
