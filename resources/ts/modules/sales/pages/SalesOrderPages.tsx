import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GdnTable,
    SalesActivityTimeline,
    SalesCreditCheckPanel,
    SalesInvoiceTable,
    SalesOrderForm,
    SalesOrderLineTable,
    SalesOrderSummaryCard,
    SalesOrderTable,
    SalesPaymentTable,
    SalesSourceReferencePanel,
    SalesStockAvailabilityPanel,
    SalesWorkflowActions,
} from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { GoodsDeliveryNote, SalesAuditEntry, SalesInvoice, SalesOrder, SalesPayment } from '../types/sales.types';

export function SalesOrderListPage() {
    const [rows, setRows] = useState<SalesOrder[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        salesApi.orders.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load sales orders.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/orders/new"><Button>Create SO</Button></Link>} eyebrow="Sales" subtitle="Customer commitments, credit checks, pricing previews, reservations, approvals, and flexible downstream delivery/invoice flows." title="Sales Orders" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'submitted', 'approved', 'partially_delivered', 'delivered', 'closed', 'cancelled', 'reversed'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists."
                searchPlaceholder="Search SO number, customer, status, item..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Sales order API unavailable" /> : null}
            {!error && rows.length ? <SalesOrderTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No sales orders returned yet." title="No sales orders" /> : null}
        </div>
    );
}

export function SalesOrderCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Sales Orders" subtitle="Create with lines. Backend validates customer, credit, item, UOM, pricing, tax, stock, totals, and workflow." title="Create Sales Order" />
            <SalesOrderForm />
        </div>
    );
}

export function SalesOrderEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Sales Orders" subtitle="Update draft sales order input. Backend remains authoritative for totals, stock, credit, and status." title="Edit Sales Order" />
            <SalesOrderForm mode="edit" />
        </div>
    );
}

export function SalesOrderDetailPage() {
    const { id = 'so-001' } = useParams();
    const [order, setOrder] = useState<SalesOrder>();
    const [deliveries, setDeliveries] = useState<GoodsDeliveryNote[]>([]);
    const [history, setHistory] = useState<SalesAuditEntry[]>([]);
    const [invoices, setInvoices] = useState<SalesInvoice[]>([]);
    const [payments, setPayments] = useState<SalesPayment[]>([]);
    const [activeTab, setActiveTab] = useState('overview');
    const [tabError, setTabError] = useState('');
    const [tabLoading, setTabLoading] = useState('');
    const loadedTabsRef = useRef(new Set<string>());

    useEffect(() => {
        let mounted = true;
        loadedTabsRef.current.clear();
        setActiveTab('overview');
        setOrder(undefined);
        salesApi.orders.get(id).then((response) => {
            if (mounted) setOrder(response.data);
        });
        return () => {
            mounted = false;
        };
    }, [id]);

    useEffect(() => {
        if (!order || !['deliveries', 'invoices', 'payments', 'audit'].includes(activeTab) || loadedTabsRef.current.has(activeTab)) {
            return;
        }

        const currentOrder = order;
        let mounted = true;
        setTabLoading(activeTab);
        setTabError('');

        async function loadTab(): Promise<void> {
            try {
                if (activeTab === 'deliveries') {
                    const response = await salesApi.deliveries.list({ perPage: 20, search: currentOrder.soNumber });
                    if (mounted) setDeliveries(response.data);
                } else if (activeTab === 'invoices') {
                    const response = await salesApi.invoices.list({ perPage: 20, search: currentOrder.soNumber });
                    if (mounted) setInvoices(response.data);
                } else if (activeTab === 'payments') {
                    const response = await salesApi.payments.list({ perPage: 20 });
                    if (mounted) setPayments(response.data);
                } else {
                    const response = await salesApi.orders.history(id).catch(() => ({ data: [] }));
                    if (mounted) setHistory(response.data);
                }

                if (mounted) loadedTabsRef.current.add(activeTab);
            } catch (caught: unknown) {
                if (mounted) setTabError(caught instanceof Error ? caught.message : 'Unable to load tab data.');
            } finally {
                if (mounted) setTabLoading('');
            }
        }

        void loadTab();

        return () => {
            mounted = false;
        };
    }, [activeTab, id, order]);

    if (!order) {
        return <EmptyState description="Loading sales order details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to={`/sales/orders/${order.id}/edit`}><Button variant="secondary">Edit</Button></Link><Button variant="blue">Backend Actions</Button></>} eyebrow="Sales Order" subtitle="Detail workspace for SO lines, deliveries, invoices, documents, workflow, and audit." title={order.soNumber} />
            <SalesOrderSummaryCard order={order} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Lines', value: 'lines' },
                    { label: 'Deliveries', value: 'deliveries' },
                    { label: 'Invoices', value: 'invoices' },
                    { label: 'Payments', value: 'payments' },
                    { label: 'Documents', value: 'documents' },
                    { label: 'Workflow / History', value: 'workflow' },
                    { label: 'Audit', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <><SalesOrderSummaryCard order={order} /><SalesCreditCheckPanel /><SalesStockAvailabilityPanel /></> : null}
            {activeTab === 'lines' ? <SalesOrderLineTable rows={order.lines} /> : null}
            {activeTab === 'deliveries' ? tabLoading === 'deliveries' ? <EmptyState description="Loading related deliveries..." title="Loading deliveries" /> : tabError ? <EmptyState description={tabError} title="Deliveries unavailable" /> : <GdnTable rows={deliveries.filter((gdn) => gdn.sourceOrder === order.soNumber || gdn.sourceOrder === order.id)} /> : null}
            {activeTab === 'invoices' ? tabLoading === 'invoices' ? <EmptyState description="Loading related invoices..." title="Loading invoices" /> : tabError ? <EmptyState description={tabError} title="Invoices unavailable" /> : <SalesInvoiceTable rows={invoices.filter((invoice) => invoice.sourceReference === order.soNumber || invoice.sourceReference === order.id)} /> : null}
            {activeTab === 'payments' ? tabLoading === 'payments' ? <EmptyState description="Loading customer payments..." title="Loading payments" /> : tabError ? <EmptyState description={tabError} title="Payments unavailable" /> : <SalesPaymentTable rows={payments.filter((payment) => payment.customerId === order.customerId)} /> : null}
            {activeTab === 'documents' ? <SalesSourceReferencePanel reference={order.soNumber} /> : null}
            {activeTab === 'workflow' ? <><SalesWorkflowActions entityId={order.id} entityType="sales_order" status={order.status} /><EmptyState description="Finance posting is posted through the backend workflow action for this sales order." title="Finance preview source-scoped" /></> : null}
            {activeTab === 'audit' ? tabLoading === 'audit' ? <EmptyState description="Loading audit timeline..." title="Loading audit" /> : tabError ? <EmptyState description={tabError} title="Audit unavailable" /> : <SalesActivityTimeline rows={history} /> : null}
        </div>
    );
}
