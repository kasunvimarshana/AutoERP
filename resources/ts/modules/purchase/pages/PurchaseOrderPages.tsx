import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
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
import { financePostingPreview, grns, purchaseActivity, purchaseInvoices, purchasePayments } from '../mock/purchaseMock';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseOrder } from '../types/purchase.types';

export function PurchaseOrderListPage() {
    const [rows, setRows] = useState<PurchaseOrder[]>([]);

    useEffect(() => {
        purchaseApi.orders.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/orders/new"><Button>Create PO</Button></Link>} eyebrow="Purchase" subtitle="Supplier commitments, order lines, approvals, and flexible downstream GRN/invoice flows." title="Purchase Orders" />
            <SearchFilterBar placeholder="Search PO number, supplier, status, item..." />
            {rows.length ? <PurchaseOrderTable rows={rows} /> : <EmptyState description="No purchase orders returned yet." title="No purchase orders" />}
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
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Orders" subtitle="Update draft purchase order input. Backend remains authoritative for totals and status." title="Edit Purchase Order" />
            <PurchaseOrderForm mode="edit" />
        </div>
    );
}

export function PurchaseOrderDetailPage() {
    const { id = 'po-001' } = useParams();
    const [order, setOrder] = useState<PurchaseOrder>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.orders.get(id).then((response) => setOrder(response.data));
    }, [id]);

    if (!order) {
        return <EmptyState description="Loading purchase order details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to={`/purchase/orders/${order.id}/edit`}><Button variant="secondary">Edit</Button></Link><Button variant="blue">Backend Actions</Button></>}
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
            {activeTab === 'grns' ? <GrnTable rows={grns.filter((grn) => grn.sourcePo === order.poNumber)} /> : null}
            {activeTab === 'invoices' ? <PurchaseInvoiceTable rows={purchaseInvoices.filter((invoice) => invoice.sourceReference === 'GRN-2026-0007')} /> : null}
            {activeTab === 'payments' ? <PurchasePaymentTable rows={purchasePayments} /> : null}
            {activeTab === 'documents' ? <PurchaseSourceReferencePanel reference={order.poNumber} /> : null}
            {activeTab === 'workflow' ? <><PurchaseWorkflowActions entityId={order.id} entityType="purchase_order" status={order.status} /><PurchaseFinancePostingPanel preview={financePostingPreview} /></> : null}
            {activeTab === 'audit' ? <PurchaseActivityTimeline rows={purchaseActivity} /> : null}
        </div>
    );
}
