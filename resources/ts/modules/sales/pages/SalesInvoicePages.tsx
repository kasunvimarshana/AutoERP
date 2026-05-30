import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    SalesActivityTimeline,
    SalesFinancePostingPanel,
    SalesInvoiceCalculationPanel,
    SalesInvoiceDocumentPanel,
    SalesInvoiceForm,
    SalesInvoiceLineTable,
    SalesInvoiceTable,
    SalesPaymentTable,
    SalesReturnTable,
    SalesSourceReferencePanel,
    SalesWorkflowActions,
} from '../components/SalesComponents';
import { financePostingPreview, salesActivity, salesPayments, salesReturns } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { SalesInvoice } from '../types/sales.types';

export function SalesInvoiceListPage() {
    const [rows, setRows] = useState<SalesInvoice[]>([]);

    useEffect(() => {
        salesApi.invoices.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/invoices/new"><Button>Create Customer Invoice</Button></Link>} eyebrow="Sales" subtitle="Customer invoices are receivable authority. Backend previews pricing, tax, discounts, UOM, AR, COGS, documents, and balances." title="Customer Invoices" />
            <SearchFilterBar placeholder="Search invoice number, customer, source SO/GDN, status..." />
            {rows.length ? <SalesInvoiceTable rows={rows} /> : <EmptyState description="No customer invoices returned yet." title="No invoices" />}
        </div>
    );
}

export function SalesInvoiceCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Customer Invoice" subtitle="Create direct, from sales order, from GDN, or from multiple GDNs. Backend owns all calculations." title="Create Customer Invoice" />
            <SalesInvoiceForm />
        </div>
    );
}

export function SalesInvoiceEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Customer Invoice" subtitle="Edit draft invoice inputs. Totals and posting effects come from backend preview/posting." title="Edit Customer Invoice" />
            <SalesInvoiceForm mode="edit" />
        </div>
    );
}

export function SalesInvoiceDetailPage() {
    const { id = 'sinv-001' } = useParams();
    const [invoice, setInvoice] = useState<SalesInvoice>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.invoices.get(id).then((response) => setInvoice(response.data));
    }, [id]);

    if (!invoice) {
        return <EmptyState description="Loading customer invoice details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to={`/sales/invoices/${invoice.id}/edit`}><Button variant="secondary">Edit</Button></Link><Button variant="blue">Post via Backend</Button></>} eyebrow="Customer Invoice" subtitle="Invoice detail with calculation preview, source matching, document, payment allocation, AR/COGS posting, returns, and audit." title={invoice.invoiceNumber} />
            <Tabs active={activeTab} items={[{ label: 'Overview', value: 'overview' }, { label: 'Lines', value: 'lines' }, { label: 'Calculation Preview', value: 'calculation' }, { label: 'Source Order/Delivery', value: 'source' }, { label: 'Document', value: 'document' }, { label: 'Payments / Allocations', value: 'payments' }, { label: 'Finance / AR / COGS Posting', value: 'finance' }, { label: 'Returns', value: 'returns' }, { label: 'History / Audit', value: 'history' }]} onChange={setActiveTab} />
            {activeTab === 'overview' ? <SalesWorkflowActions entityId={invoice.id} entityType="sales_invoice" status={invoice.status} /> : null}
            {activeTab === 'lines' ? <SalesInvoiceLineTable rows={invoice.lines} /> : null}
            {activeTab === 'calculation' ? <SalesInvoiceCalculationPanel /> : null}
            {activeTab === 'source' ? <SalesSourceReferencePanel reference={invoice.sourceReference} /> : null}
            {activeTab === 'document' ? <SalesInvoiceDocumentPanel /> : null}
            {activeTab === 'payments' ? <SalesPaymentTable rows={salesPayments} /> : null}
            {activeTab === 'finance' ? <SalesFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'returns' ? <SalesReturnTable rows={salesReturns.filter((record) => record.sourceReference === invoice.invoiceNumber)} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={salesActivity} /> : null}
        </div>
    );
}
