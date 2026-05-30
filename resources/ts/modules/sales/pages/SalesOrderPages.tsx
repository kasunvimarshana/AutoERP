import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GdnTable,
    SalesActivityTimeline,
    SalesCreditCheckPanel,
    SalesFinancePostingPanel,
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
import { creditCheckPreview, financePostingPreview, gdns, salesActivity, salesInvoices, salesPayments, stockAvailabilityPreview } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { SalesOrder } from '../types/sales.types';

export function SalesOrderListPage() {
    const [rows, setRows] = useState<SalesOrder[]>([]);

    useEffect(() => {
        salesApi.orders.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/orders/new"><Button>Create SO</Button></Link>} eyebrow="Sales" subtitle="Customer commitments, credit checks, pricing previews, reservations, approvals, and flexible downstream delivery/invoice flows." title="Sales Orders" />
            <SearchFilterBar placeholder="Search SO number, customer, status, item..." />
            {rows.length ? <SalesOrderTable rows={rows} /> : <EmptyState description="No sales orders returned yet." title="No sales orders" />}
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
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.orders.get(id).then((response) => setOrder(response.data));
    }, [id]);

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
            {activeTab === 'overview' ? <><SalesOrderSummaryCard order={order} /><SalesCreditCheckPanel result={creditCheckPreview} /><SalesStockAvailabilityPanel preview={stockAvailabilityPreview} /></> : null}
            {activeTab === 'lines' ? <SalesOrderLineTable rows={order.lines} /> : null}
            {activeTab === 'deliveries' ? <GdnTable rows={gdns.filter((gdn) => gdn.sourceOrder === order.soNumber)} /> : null}
            {activeTab === 'invoices' ? <SalesInvoiceTable rows={salesInvoices.filter((invoice) => invoice.sourceReference === 'GDN-2026-0007')} /> : null}
            {activeTab === 'payments' ? <SalesPaymentTable rows={salesPayments} /> : null}
            {activeTab === 'documents' ? <SalesSourceReferencePanel reference={order.soNumber} /> : null}
            {activeTab === 'workflow' ? <><SalesWorkflowActions entityId={order.id} entityType="sales_order" status={order.status} /><SalesFinancePostingPanel preview={financePostingPreview} /></> : null}
            {activeTab === 'audit' ? <SalesActivityTimeline rows={salesActivity} /> : null}
        </div>
    );
}
