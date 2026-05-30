import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    PurchaseActivityTimeline,
    PurchaseFinancePostingPanel,
    PurchaseInvoiceCalculationPanel,
    PurchaseInvoiceDocumentPanel,
    PurchaseInvoiceForm,
    PurchaseInvoiceLineTable,
    PurchaseInvoiceTable,
    PurchasePaymentTable,
    PurchaseReturnTable,
    PurchaseSourceReferencePanel,
    PurchaseWorkflowActions,
} from '../components/PurchaseComponents';
import { financePostingPreview, purchaseActivity, purchasePayments, purchaseReturns } from '../mock/purchaseMock';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseInvoice } from '../types/purchase.types';

export function PurchaseInvoiceListPage() {
    const [rows, setRows] = useState<PurchaseInvoice[]>([]);

    useEffect(() => {
        purchaseApi.invoices.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/invoices/new"><Button>Create Supplier Invoice</Button></Link>} eyebrow="Purchase" subtitle="Supplier invoices are payable authority. Backend previews totals, tax, discounts, UOM, AP, documents, and balances." title="Supplier Invoices" />
            <SearchFilterBar placeholder="Search invoice number, supplier, source PO/GRN, status..." />
            {rows.length ? <PurchaseInvoiceTable rows={rows} /> : <EmptyState description="No supplier invoices returned yet." title="No invoices" />}
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
            <PageHeader eyebrow="Supplier Invoice" subtitle="Edit draft invoice inputs. Totals and posting effects come from backend preview/posting." title="Edit Supplier Invoice" />
            <PurchaseInvoiceForm mode="edit" />
        </div>
    );
}

export function PurchaseInvoiceDetailPage() {
    const { id = 'pinv-001' } = useParams();
    const [invoice, setInvoice] = useState<PurchaseInvoice>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.invoices.get(id).then((response) => setInvoice(response.data));
    }, [id]);

    if (!invoice) {
        return <EmptyState description="Loading supplier invoice details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to={`/purchase/invoices/${invoice.id}/edit`}><Button variant="secondary">Edit</Button></Link><Button variant="blue">Post via Backend</Button></>}
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
            {activeTab === 'overview' ? <PurchaseWorkflowActions entityId={invoice.id} entityType="purchase_invoice" status={invoice.status} /> : null}
            {activeTab === 'lines' ? <PurchaseInvoiceLineTable rows={invoice.lines} /> : null}
            {activeTab === 'calculation' ? <PurchaseInvoiceCalculationPanel /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={invoice.sourceReference} /> : null}
            {activeTab === 'document' ? <PurchaseInvoiceDocumentPanel /> : null}
            {activeTab === 'payments' ? <PurchasePaymentTable rows={purchasePayments} /> : null}
            {activeTab === 'finance' ? <PurchaseFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'returns' ? <PurchaseReturnTable rows={purchaseReturns.filter((record) => record.sourceReference === invoice.invoiceNumber)} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={purchaseActivity} /> : null}
        </div>
    );
}
