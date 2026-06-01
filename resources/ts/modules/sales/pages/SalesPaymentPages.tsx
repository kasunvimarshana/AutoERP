import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
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
import { salesApi } from '../services/salesApi';
import type { CustomerAdvance, CustomerRefund, SalesAuditEntry, SalesPayment } from '../types/sales.types';

export function SalesPaymentListPage() {
    const [rows, setRows] = useState<SalesPayment[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        salesApi.payments.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load payments.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/payments/new"><Button>Create Customer Payment</Button></Link>} eyebrow="Sales" subtitle="Sales-scoped customer payment workspace backed by the generic Payment module." title="Customer Payments" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'posted', 'allocated', 'voided', 'reversed'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists."
                searchPlaceholder="Search payment number, customer, method, invoice..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Payment API unavailable" /> : null}
            {!error && rows.length ? <SalesPaymentTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No payments returned yet." title="No payments" /> : null}
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
    const [history, setHistory] = useState<SalesAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.payments.get(id).then((response) => setPayment(response.data));
        setHistory([]);
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
            {activeTab === 'finance' ? <EmptyState description="Payment posting preview is exposed through the generic Payment/Finance modules and is not duplicated in Sales." title="Finance preview unavailable" /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={history} /> : null}
        </div>
    );
}

export function CustomerAdvanceListPage() {
    const [advances, setAdvances] = useState<CustomerAdvance[]>([]);
    useEffect(() => {
        salesApi.advances.list().then((response) => setAdvances(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button disabled title="Advance creation requires a sales source document in the current backend route.">Create Advance</Button>} eyebrow="Sales" subtitle="Customer advances and later allocations. Remaining balance is backend-owned." title="Customer Advances" />
            <DataToolbar onSearchChange={() => undefined} savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists." searchPlaceholder="Search customer advances..." />
            {advances.length ? <CustomerAdvancePanel advances={advances} /> : <EmptyState description="No customer advances returned by the backend." title="No advances" />}
        </div>
    );
}

export function CustomerRefundListPage() {
    const [refunds, setRefunds] = useState<CustomerRefund[]>([]);
    useEffect(() => {
        salesApi.refunds.list().then((response) => setRefunds(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button disabled title="Refund creation requires a posted payment id in the current backend route.">Create Refund</Button>} eyebrow="Sales" subtitle="Customer refunds linked to sales returns or credits. Refund amount and posting are backend-owned." title="Customer Refunds" />
            <DataToolbar onSearchChange={() => undefined} savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists." searchPlaceholder="Search customer refunds..." />
            {refunds.length ? <CustomerRefundPanel refunds={refunds} /> : <EmptyState description="No customer refunds returned by the backend." title="No refunds" />}
        </div>
    );
}
