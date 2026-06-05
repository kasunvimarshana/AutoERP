import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GrnInventoryEffectPanel,
    PurchaseActivityTimeline,
    PurchaseFinancePostingPanel,
    PurchaseReturnForm,
    PurchaseReturnLineTable,
    PurchaseReturnTable,
    PurchaseSourceReferencePanel,
    SupplierRefundPanel,
} from '../components/PurchaseComponents';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchaseAuditEntry, PurchaseReturn, SupplierRefund } from '../types/purchase.types';

export function PurchaseReturnListPage() {
    const [rows, setRows] = useState<PurchaseReturn[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        purchaseApi.returns.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load returns.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/purchase/returns/new"><Button>Create Return</Button></Link>} eyebrow="Purchase" subtitle="Purchase returns with backend-owned returnable quantity, stock/AP effects, and refund eligibility." title="Purchase Returns" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'approved', 'posted', 'refunded', 'cancelled'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Purchase lists."
                searchPlaceholder="Search return number..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Purchase return API unavailable" /> : null}
            {!error && rows.length ? <PurchaseReturnTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No purchase returns returned yet." title="No returns" /> : null}
        </div>
    );
}

export function PurchaseReturnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Return" subtitle="Create from invoice, GRN, PO, or direct source. Backend validates returnable quantities and effects." title="Create Purchase Return" />
            <PurchaseReturnForm />
        </div>
    );
}

export function PurchaseReturnEditPage() {
    const { id = '' } = useParams();
    const [record, setRecord] = useState<PurchaseReturn>();

    useEffect(() => {
        if (id) purchaseApi.returns.get(id).then((response) => setRecord(response.data));
    }, [id]);

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Return" subtitle="Update return input. Backend validates returnable quantities and effects." title="Edit Purchase Return" />
            {record ? <PurchaseReturnForm initialReturn={record} /> : <EmptyState description="Loading purchase return for editing..." title="Loading" />}
        </div>
    );
}

export function PurchaseReturnDetailPage() {
    const { id = 'pret-001' } = useParams();
    const [record, setRecord] = useState<PurchaseReturn>();
    const [refunds, setRefunds] = useState<SupplierRefund[]>([]);
    const [history, setHistory] = useState<PurchaseAuditEntry[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        purchaseApi.returns.get(id).then((response) => setRecord(response.data));
        purchaseApi.refunds.list().then((response) => setRefunds(response.data));
        setHistory([]);
    }, [id]);

    if (!record) {
        return <EmptyState description="Loading purchase return details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Purchase Return" subtitle="Return detail with source, inventory effect, AP effect, refunds, and audit." title={record.returnNumber} />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Return Lines', value: 'lines' },
                    { label: 'Source Reference', value: 'source' },
                    { label: 'Inventory Effect', value: 'inventory' },
                    { label: 'AP/Finance Effect', value: 'finance' },
                    { label: 'Refunds', value: 'refunds' },
                    { label: 'History / Audit', value: 'history' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PurchaseReturnTable rows={[record]} /> : null}
            {activeTab === 'lines' ? <PurchaseReturnLineTable rows={record.lines} /> : null}
            {activeTab === 'source' ? <PurchaseSourceReferencePanel reference={record.sourceReference} /> : null}
            {activeTab === 'inventory' ? <GrnInventoryEffectPanel effects={[]} /> : null}
            {activeTab === 'finance' ? <EmptyState description="Return finance preview requires a backend preview endpoint before AP effects can be displayed." title="Finance preview unavailable" /> : null}
            {activeTab === 'refunds' ? <SupplierRefundPanel refunds={refunds.filter((refund) => refund.sourceReference === record.returnNumber || refund.sourceReference === record.id)} /> : null}
            {activeTab === 'history' ? <PurchaseActivityTimeline rows={history} /> : null}
        </div>
    );
}
