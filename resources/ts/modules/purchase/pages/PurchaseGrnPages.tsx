import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GrnForm,
    GrnInventoryEffectPanel,
    GrnLineTable,
    GrnTable,
    PurchaseActivityTimeline,
    PurchaseInvoiceTable,
    PurchaseSourceReferencePanel,
    PurchaseWorkflowActions,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { GoodsReceivedNote, PurchaseAuditEntry, PurchaseInvoice } from '../types/purchase.types';

export function GrnListPage() {
    const [rows, setRows] = useState<GoodsReceivedNote[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        purchaseApi.grns.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load GRNs.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/grns/new"><Button>Create GRN</Button></Link>} eyebrow="Purchase" subtitle="Goods receipt notes manage received quantities and backend-owned inventory effects." title="Goods Received Notes" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'submitted', 'confirmed', 'posted', 'cancelled'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists."
                searchPlaceholder="Search GRN number..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="GRN API unavailable" /> : null}
            {!error && rows.length ? <GrnTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No GRNs returned yet." title="No GRNs" /> : null}
        </div>
    );
}

export function GrnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="GRN" subtitle="Create from PO or direct GRN when settings allow. Backend validates stock receiving rules." title="Create Goods Receipt Note" />
            <GrnForm />
        </div>
    );
}

export function GrnEditPage() {
    const { id = '' } = useParams();
    const [grn, setGrn] = useState<GoodsReceivedNote>();

    useEffect(() => {
        if (id) purchaseApi.grns.get(id).then((response) => setGrn(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="GRN" subtitle="Update draft GRN inputs. Stock movements are not calculated in the frontend." title="Edit Goods Receipt Note" />
            {grn ? <GrnForm initialGrn={grn} mode="edit" /> : <EmptyState description="Loading GRN for editing..." title="Loading" />}
        </div>
    );
}

export function GrnDetailPage() {
    const { id = 'grn-001' } = useParams();
    const [grn, setGrn] = useState<GoodsReceivedNote>();
    const [invoices, setInvoices] = useState<PurchaseInvoice[]>([]);
    const [history, setHistory] = useState<PurchaseAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.grns.get(id).then((response) => setGrn(response.data));
        purchaseApi.invoices.list().then((response) => setInvoices(response.data));
        purchaseApi.grns.history(id).then((response) => setHistory(response.data)).catch(() => setHistory([]));
    }, [id]);

    if (!grn) {
        return <EmptyState description="Loading GRN details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to={`/purchase/grns/${grn.id}/edit`}><Button variant="secondary">Edit</Button></Link>} eyebrow="Goods Receipt Note" subtitle="GRN detail with received lines, source PO, inventory effect, invoices, and audit." title={grn.grnNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Received Lines', value: 'lines' },
                    { label: 'Source PO', value: 'source' },
                    { label: 'Inventory Effect', value: 'inventory' },
                    { label: 'Invoices', value: 'invoices' },
                    { label: 'Documents', value: 'documents' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseWorkflowActions entityId={grn.id} entityType="grn_header" status={grn.status} /> : null}
            {activeTab === 'lines' ? <GrnLineTable rows={grn.lines} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={grn.sourcePo} /> : null}
            {activeTab === 'inventory' ? <GrnInventoryEffectPanel effects={[]} /> : null}
            {activeTab === 'invoices' ? <PurchaseInvoiceTable rows={invoices.filter((invoice) => invoice.sourceReference === grn.grnNumber || invoice.sourceReference === grn.id)} /> : null}
            {activeTab === 'documents' ? <PurchaseSourceReferencePanel reference={grn.grnNumber} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={history} /> : null}
        </div>
    );
}
