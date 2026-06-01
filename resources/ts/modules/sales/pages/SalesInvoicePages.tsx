import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar } from '../../../shared/components/data/DataToolbar';
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
import { salesApi } from '../services/salesApi';
import type { SalesAuditEntry, SalesInvoice, SalesPayment, SalesReturn } from '../types/sales.types';

export function SalesInvoiceListPage() {
    const [rows, setRows] = useState<SalesInvoice[]>([]);
    const [query, setQuery] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        salesApi.invoices.list({ search: query })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load customer invoices.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/invoices/new"><Button>Create Customer Invoice</Button></Link>} eyebrow="Sales" subtitle="Customer invoices are receivable authority. Backend previews pricing, tax, discounts, UOM, AR, COGS, documents, and balances." title="Customer Invoices" />
            <DataToolbar isLoading={isLoading} onSearchChange={setQuery} savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists." searchPlaceholder="Search invoice number, customer, source SO/GDN, status..." searchValue={query} />
            {error ? <EmptyState description={error} title="Customer invoice API unavailable" /> : null}
            {!error && rows.length ? <SalesInvoiceTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="The current backend exposes source-scoped invoices. Global list reads real Document module invoice records." title="No invoices returned" /> : null}
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
    const [history, setHistory] = useState<SalesAuditEntry[]>([]);
    const [payments, setPayments] = useState<SalesPayment[]>([]);
    const [returns, setReturns] = useState<SalesReturn[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        let active = true;
        salesApi.invoices.get(id).then((response) => active && setInvoice(response.data));

        return () => {
            active = false;
        };
    }, [id]);

    useEffect(() => {
        let active = true;

        if (activeTab === 'payments' && payments.length === 0) {
            salesApi.payments.list({ perPage: 25 }).then((response) => active && setPayments(response.data));
        } else if (activeTab === 'returns' && returns.length === 0) {
            salesApi.returns.list({ perPage: 25 }).then((response) => active && setReturns(response.data));
        } else if (activeTab === 'history' && history.length === 0) {
            setHistory([]);
        }

        return () => {
            active = false;
        };
    }, [activeTab, history.length, payments.length, returns.length]);

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
            {activeTab === 'payments' ? <SalesPaymentTable rows={payments.filter((payment) => payment.customerId === invoice.customerId)} /> : null}
            {activeTab === 'finance' ? <EmptyState description="Posting preview requires a persisted sales source context. Backend invoice detail is source-scoped." title="Finance preview unavailable" /> : null}
            {activeTab === 'returns' ? <SalesReturnTable rows={returns.filter((record) => record.sourceReference === invoice.invoiceNumber || record.sourceReference === invoice.id)} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={history} /> : null}
        </div>
    );
}
