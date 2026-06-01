import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    PurchaseActivityTimeline,
    PurchaseAdvancePanel,
    PurchasePaymentAllocationPanel,
    PurchasePaymentForm,
    PurchasePaymentTable,
    PurchaseSourceReferencePanel,
    SupplierRefundPanel,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseAdvance, PurchaseAuditEntry, PurchasePayment, SupplierRefund } from '../types/purchase.types';

export function PurchasePaymentListPage() {
    const [rows, setRows] = useState<PurchasePayment[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        purchaseApi.payments.list({ search: query, status })
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
            <PageHeader actions={<Link to="/purchase/payments/new"><Button>Create Supplier Payment</Button></Link>} eyebrow="Purchase" subtitle="Purchase-scoped supplier payment workspace backed by the generic Payment module." title="Supplier Payments" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'posted', 'allocated', 'voided', 'reversed'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists."
                searchPlaceholder="Search payment number..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Payment API unavailable" /> : null}
            {!error && rows.length ? <PurchasePaymentTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No payments returned yet." title="No payments" /> : null}
        </div>
    );
}

export function PurchasePaymentCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Supplier Payment" subtitle="Collect supplier payment input and request backend allocation preview before posting." title="Create Supplier Payment" />
            <PurchasePaymentForm />
        </div>
    );
}

export function PurchasePaymentDetailPage() {
    const { id = 'pay-001' } = useParams();
    const [payment, setPayment] = useState<PurchasePayment>();
    const [history, setHistory] = useState<PurchaseAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.payments.get(id).then((response) => setPayment(response.data));
        setHistory([]);
    }, [id]);

    if (!payment) {
        return <EmptyState description="Loading supplier payment details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Backend Actions</Button>} eyebrow="Supplier Payment" subtitle="Payment detail with allocation, source invoice, finance posting, and history." title={payment.paymentNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Allocations', value: 'allocations' },
                    { label: 'Source Invoice', value: 'source' },
                    { label: 'Finance Posting', value: 'finance' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchasePaymentTable rows={[payment]} /> : null}
            {activeTab === 'allocations' ? <PurchasePaymentAllocationPanel allocations={payment.allocations} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={payment.reference} /> : null}
            {activeTab === 'finance' ? <EmptyState description="Payment posting preview is exposed through the generic Payment/Finance modules and is not duplicated in Purchase." title="Finance preview unavailable" /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={history} /> : null}
        </div>
    );
}

export function PurchaseAdvanceListPage() {
    const [advances, setAdvances] = useState<PurchaseAdvance[]>([]);
    useEffect(() => {
        purchaseApi.advances.list().then((response) => setAdvances(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button disabled title="Advance creation requires a purchase source document in the current backend route.">Create Advance</Button>} eyebrow="Purchase" subtitle="Supplier advances and later allocations. Remaining balance is backend-owned." title="Supplier Advances" />
            <DataToolbar onSearchChange={() => undefined} savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists." searchPlaceholder="Search supplier advances..." />
            {advances.length ? <PurchaseAdvancePanel advances={advances} /> : <EmptyState description="No supplier advances returned by the backend." title="No advances" />}
        </div>
    );
}

export function SupplierRefundListPage() {
    const [refunds, setRefunds] = useState<SupplierRefund[]>([]);
    useEffect(() => {
        purchaseApi.refunds.list().then((response) => setRefunds(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button disabled title="Refund creation requires a posted payment id in the current backend route.">Create Refund</Button>} eyebrow="Purchase" subtitle="Supplier refunds linked to purchase returns or credits. Refund amount and posting are backend-owned." title="Supplier Refunds" />
            <DataToolbar onSearchChange={() => undefined} savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists." searchPlaceholder="Search supplier refunds..." />
            {refunds.length ? <SupplierRefundPanel refunds={refunds} /> : <EmptyState description="No supplier refunds returned by the backend." title="No refunds" />}
        </div>
    );
}
