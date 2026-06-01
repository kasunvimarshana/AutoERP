import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GrnTable,
    PurchaseActivityTimeline,
    PurchaseFinancePostingPanel,
    PurchaseInvoiceTable,
    PurchaseOrderForm,
    PurchaseOrderLineTable,
    PurchaseOrderSummaryCard,
    PurchaseOrderTable,
    PurchasePaymentTable,
    PurchaseSourceReferencePanel,
    PurchaseWorkflowActions,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { GoodsReceivedNote, PurchaseInvoice, PurchaseOrder, PurchasePayment, PurchaseAuditEntry } from '../types/purchase.types';

export function PurchaseOrderListPage() {
    const [rows, setRows] = useState<PurchaseOrder[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        purchaseApi.orders.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load purchase orders.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/orders/new"><Button>Create PO</Button></Link>} eyebrow="Purchase" subtitle="Supplier commitments, order lines, approvals, and flexible downstream GRN/invoice flows." title="Purchase Orders" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'submitted', 'approved', 'received', 'closed', 'cancelled'].map((value) => ({ label: value.replaceAll('_', ' '), value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists."
                searchPlaceholder="Search PO number..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Purchase order API unavailable" /> : null}
            {!error && rows.length ? <PurchaseOrderTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No purchase orders returned yet." title="No purchase orders" /> : null}
        </div>
    );
}

export function PurchaseOrderCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Orders" subtitle="Create with lines. Backend validates supplier, item, UOM, pricing, tax, totals, and workflow." title="Create Purchase Order" />
            <PurchaseOrderForm />
        </div>
    );
}

export function PurchaseOrderEditPage() {
    const { id = '' } = useParams();
    const [order, setOrder] = useState<PurchaseOrder>();

    useEffect(() => {
        if (id) purchaseApi.orders.get(id).then((response) => setOrder(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Orders" subtitle="Update draft purchase order input. Backend remains authoritative for totals and status." title="Edit Purchase Order" />
            {order ? <PurchaseOrderForm initialOrder={order} mode="edit" /> : <EmptyState description="Loading purchase order for editing..." title="Loading" />}
        </div>
    );
}

export function PurchaseOrderDetailPage() {
    const { id = 'po-001' } = useParams();
    const [order, setOrder] = useState<PurchaseOrder>();
    const [grns, setGrns] = useState<GoodsReceivedNote[]>([]);
    const [invoices, setInvoices] = useState<PurchaseInvoice[]>([]);
    const [payments, setPayments] = useState<PurchasePayment[]>([]);
    const [history, setHistory] = useState<PurchaseAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.orders.get(id).then((response) => setOrder(response.data));
        purchaseApi.grns.list().then((response) => setGrns(response.data));
        purchaseApi.invoices.list().then((response) => setInvoices(response.data));
        purchaseApi.payments.list().then((response) => setPayments(response.data));
        purchaseApi.orders.history(id).then((response) => setHistory(response.data)).catch(() => setHistory([]));
    }, [id]);

    if (!order) {
        return <EmptyState description="Loading purchase order details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to={`/purchase/orders/${order.id}/edit`}><Button variant="secondary">Edit</Button></Link>}
                eyebrow="Purchase Order"
                subtitle="Detail workspace for PO lines, receipts, invoices, documents, workflow, and audit."
                title={order.poNumber}
            />
            <PurchaseOrderSummaryCard order={order} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Lines', value: 'lines' },
                    { label: 'GRNs', value: 'grns' },
                    { label: 'Invoices', value: 'invoices' },
                    { label: 'Payments', value: 'payments' },
                    { label: 'Documents', value: 'documents' },
                    { label: 'Workflow / History', value: 'workflow' },
                    { label: 'Audit', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseOrderSummaryCard order={order} /> : null}
            {activeTab === 'lines' ? <PurchaseOrderLineTable rows={order.lines} /> : null}
            {activeTab === 'grns' ? <GrnTable rows={grns.filter((grn) => grn.sourcePo === order.poNumber || grn.sourcePo === order.id)} /> : null}
            {activeTab === 'invoices' ? <PurchaseInvoiceTable rows={invoices.filter((invoice) => invoice.sourceReference === order.poNumber || invoice.sourceReference === order.id)} /> : null}
            {activeTab === 'payments' ? <PurchasePaymentTable rows={payments.filter((payment) => payment.supplierId === order.supplierId)} /> : null}
            {activeTab === 'documents' ? <PurchaseSourceReferencePanel reference={order.poNumber} /> : null}
            {activeTab === 'workflow' ? <PurchaseWorkflowActions entityId={order.id} entityType="purchase_order" status={order.status} /> : null}
            {activeTab === 'audit' ? <PurchaseActivityTimeline rows={history} /> : null}
        </div>
    );
}
