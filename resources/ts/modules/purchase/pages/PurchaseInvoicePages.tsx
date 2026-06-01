import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    PurchaseActivityTimeline,
    PurchaseInvoiceDocumentPanel,
    PurchaseInvoiceForm,
    PurchaseInvoiceLineTable,
    PurchaseInvoiceTable,
    PurchasePaymentTable,
    PurchaseReturnTable,
    PurchaseSourceReferencePanel,
    PurchaseWorkflowActions,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseAuditEntry, PurchaseInvoice, PurchasePayment, PurchaseReturn } from '../types/purchase.types';

export function PurchaseInvoiceListPage() {
    const [rows, setRows] = useState<PurchaseInvoice[]>([]);
    const [query, setQuery] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        purchaseApi.invoices.list({ search: query })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load supplier invoices.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query]);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/invoices/new"><Button>Create Supplier Invoice</Button></Link>} eyebrow="Purchase" subtitle="Supplier invoices are payable authority. Backend previews totals, tax, discounts, UOM, AP, documents, and balances." title="Supplier Invoices" />
            <DataToolbar isLoading={isLoading} onSearchChange={setQuery} savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists." searchPlaceholder="Search invoice number..." searchValue={query} />
            {error ? <EmptyState description={error} title="Supplier invoice API unavailable" /> : null}
            {!error && rows.length ? <PurchaseInvoiceTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No supplier invoices match the current Purchase filters." title="No invoices returned" /> : null}
        </div>
    );
}

export function PurchaseInvoiceCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Supplier Invoice" subtitle="Create direct, from PO, from GRN, or from multiple GRNs. Backend owns all calculations." title="Create Supplier Invoice" />
            <PurchaseInvoiceForm />
        </div>
    );
}

export function PurchaseInvoiceEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Supplier Invoice" subtitle="Edit draft invoice inputs. Totals and posting effects come from Purchase and Document APIs." title="Edit Supplier Invoice" />
            <PurchaseInvoiceForm mode="edit" />
        </div>
    );
}

export function PurchaseInvoiceDetailPage() {
    const { id = '' } = useParams();
    const [invoice, setInvoice] = useState<PurchaseInvoice>();
    const [payments, setPayments] = useState<PurchasePayment[]>([]);
    const [returns, setReturns] = useState<PurchaseReturn[]>([]);
    const [history, setHistory] = useState<PurchaseAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');
    const [tabError, setTabError] = useState('');
    const [tabLoading, setTabLoading] = useState('');
    const loadedTabsRef = useRef(new Set<string>());

    useEffect(() => {
        let mounted = true;
        loadedTabsRef.current.clear();
        setActiveTab('overview');
        setInvoice(undefined);
        setHistory([]);
        purchaseApi.invoices.get(id).then((response) => {
            if (mounted) setInvoice(response.data);
        });
        return () => {
            mounted = false;
        };
    }, [id]);

    useEffect(() => {
        if (!invoice || !['payments', 'returns'].includes(activeTab) || loadedTabsRef.current.has(activeTab)) {
            return;
        }

        const currentInvoice = invoice;
        let mounted = true;
        setTabLoading(activeTab);
        setTabError('');

        async function loadTab(): Promise<void> {
            try {
                if (activeTab === 'payments') {
                    const response = await purchaseApi.payments.list({ perPage: 20 });
                    if (mounted) setPayments(response.data);
                } else {
                    const response = await purchaseApi.returns.list({ perPage: 20, search: currentInvoice.invoiceNumber });
                    if (mounted) setReturns(response.data);
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
    }, [activeTab, invoice]);

    if (!invoice) {
        return <EmptyState description="Loading supplier invoice details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to={`/purchase/invoices/${invoice.id}/edit`}><Button variant="secondary">Edit</Button></Link>}
                eyebrow="Supplier Invoice"
                subtitle="Invoice detail with calculation preview, source matching, document, payment allocation, AP posting, returns, and audit."
                title={invoice.invoiceNumber}
            />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Lines', value: 'lines' },
                    { label: 'Calculation Preview', value: 'calculation' },
                    { label: 'Source PO/GRN', value: 'source' },
                    { label: 'Document', value: 'document' },
                    { label: 'Payments / Allocations', value: 'payments' },
                    { label: 'Finance / AP Posting', value: 'finance' },
                    { label: 'Returns', value: 'returns' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseWorkflowActions entityId={invoice.id} entityType="purchase_invoice" sourceId={invoice.sourceId} sourceType={invoice.sourceType} status={invoice.status} /> : null}
            {activeTab === 'lines' ? <PurchaseInvoiceLineTable rows={invoice.lines} /> : null}
            {activeTab === 'calculation' ? <EmptyState description="Calculation previews are requested from the backend before create/update, so this detail view only shows persisted invoice totals." title="No unsaved calculation preview" /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={invoice.sourceReference} /> : null}
            {activeTab === 'document' ? <PurchaseInvoiceDocumentPanel entityId={invoice.sourceId} entityType={invoice.sourceType === 'grn_header' || invoice.sourceType === 'purchase_order' || invoice.sourceType === 'purchase_return' ? invoice.sourceType : undefined} /> : null}
            {activeTab === 'payments' ? tabLoading === 'payments' ? <EmptyState description="Loading supplier payments..." title="Loading payments" /> : tabError ? <EmptyState description={tabError} title="Payments unavailable" /> : <PurchasePaymentTable rows={payments.filter((payment) => payment.supplierId === invoice.supplierId)} /> : null}
            {activeTab === 'finance' ? <EmptyState description="Posting preview requires a persisted purchase source context. Backend invoice detail is source-scoped." title="Finance preview unavailable" /> : null}
            {activeTab === 'returns' ? tabLoading === 'returns' ? <EmptyState description="Loading related returns..." title="Loading returns" /> : tabError ? <EmptyState description={tabError} title="Returns unavailable" /> : <PurchaseReturnTable rows={returns.filter((record) => record.sourceReference === invoice.invoiceNumber || record.sourceReference === invoice.id)} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={history} /> : null}
        </div>
    );
}
