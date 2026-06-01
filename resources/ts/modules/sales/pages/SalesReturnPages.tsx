import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    CustomerRefundPanel,
    GdnInventoryEffectPanel,
    SalesActivityTimeline,
    SalesFinancePostingPanel,
    SalesReturnForm,
    SalesReturnLineTable,
    SalesReturnTable,
    SalesSourceReferencePanel,
} from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { CustomerRefund, SalesAuditEntry, SalesReturn } from '../types/sales.types';

export function SalesReturnListPage() {
    const [rows, setRows] = useState<SalesReturn[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        salesApi.returns.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load sales returns.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/returns/new"><Button>Create Return</Button></Link>} eyebrow="Sales" subtitle="Sales returns with backend-owned returnable quantity, stock/AR effects, and refund eligibility." title="Sales Returns" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'approved', 'posted', 'refunded', 'cancelled', 'reversed'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists."
                searchPlaceholder="Search return number, customer, source document..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Sales return API unavailable" /> : null}
            {!error && rows.length ? <SalesReturnTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No sales returns returned yet." title="No returns" /> : null}
        </div>
    );
}

export function SalesReturnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Sales Return" subtitle="Create from invoice, GDN, sales order, or direct source. Backend validates returnable quantities and effects." title="Create Sales Return" />
            <SalesReturnForm />
        </div>
    );
}

export function SalesReturnDetailPage() {
    const { id = 'sret-001' } = useParams();
    const [record, setRecord] = useState<SalesReturn>();
    const [history, setHistory] = useState<SalesAuditEntry[]>([]);
    const [refunds, setRefunds] = useState<CustomerRefund[]>([]);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.returns.get(id).then((response) => setRecord(response.data));
        salesApi.refunds.list().then((response) => setRefunds(response.data));
        setHistory([]);
    }, [id]);

    if (!record) {
        return <EmptyState description="Loading sales return details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Button variant="blue">Post via Backend</Button>} eyebrow="Sales Return" subtitle="Return detail with source, inventory effect, AR effect, refunds, and audit." title={record.returnNumber} />
            <Tabs active={activeTab} items={[{ label: 'Overview', value: 'overview' }, { label: 'Return Lines', value: 'lines' }, { label: 'Source Document', value: 'source' }, { label: 'Inventory Effect', value: 'inventory' }, { label: 'AR/Finance Effect', value: 'finance' }, { label: 'Refunds', value: 'refunds' }, { label: 'History / Audit', value: 'history' }]} onChange={setActiveTab} />
            {activeTab === 'overview' ? <SalesReturnTable rows={[record]} /> : null}
            {activeTab === 'lines' ? <SalesReturnLineTable rows={record.lines} /> : null}
            {activeTab === 'source' ? <SalesSourceReferencePanel reference={record.sourceReference} /> : null}
            {activeTab === 'inventory' ? <EmptyState description="Inventory effect is posted through the backend workflow action for this return." title="Inventory effect source-scoped" /> : null}
            {activeTab === 'finance' ? <EmptyState description="Finance posting is posted through the backend workflow action for this return." title="Finance effect source-scoped" /> : null}
            {activeTab === 'refunds' ? <CustomerRefundPanel refunds={refunds.filter((refund) => refund.sourceReference === record.returnNumber || refund.sourceReference === record.id)} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={history} /> : null}
        </div>
    );
}
