import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    GdnForm,
    GdnInventoryEffectPanel,
    GdnLineTable,
    GdnTable,
    SalesActivityTimeline,
    SalesInvoiceTable,
    SalesSourceReferencePanel,
    SalesWorkflowActions,
} from '../components/SalesComponents';
import { salesApi } from '../services/salesApi';
import type { GoodsDeliveryNote, SalesAuditEntry, SalesInvoice } from '../types/sales.types';

export function GdnListPage() {
    const [rows, setRows] = useState<GoodsDeliveryNote[]>([]);
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        let mounted = true;
        setIsLoading(true);
        setError('');
        salesApi.deliveries.list({ search: query, status })
            .then((response) => { if (mounted) setRows(response.data); })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load deliveries.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [query, status]);

    function updateFilter(filterId: string, value: DataToolbarFilterValue): void {
        if (filterId === 'status') setStatus(typeof value === 'string' ? value : '');
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/deliveries/new"><Button>Create GDN</Button></Link>} eyebrow="Sales" subtitle="Deliveries manage issued quantities and backend-owned stock effects." title="Deliveries / GDN" />
            <DataToolbar
                filterValues={{ status }}
                filters={[{ id: 'status', label: 'Status', options: ['draft', 'confirmed', 'picked', 'delivered', 'posted', 'cancelled', 'reversed'].map((value) => ({ label: value, value })), type: 'status' }]}
                isLoading={isLoading}
                onFilterChange={updateFilter}
                onRemoveFilter={() => setStatus('')}
                onResetFilters={() => setStatus('')}
                onSearchChange={setQuery}
                savedViewsDisabledReason="Saved views require a user-preferences backend for Sales lists."
                searchPlaceholder="Search GDN number, customer, SO, item..."
                searchValue={query}
            />
            {error ? <EmptyState description={error} title="Delivery API unavailable" /> : null}
            {!error && rows.length ? <GdnTable rows={rows} /> : null}
            {!error && !isLoading && !rows.length ? <EmptyState description="No deliveries returned yet." title="No deliveries" /> : null}
        </div>
    );
}

export function GdnCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Delivery / GDN" subtitle="Create from sales order or direct delivery when settings allow. Backend validates stock issue rules." title="Create Delivery" />
            <GdnForm />
        </div>
    );
}

export function GdnEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Delivery / GDN" subtitle="Update draft delivery inputs. Stock movements are not calculated in the frontend." title="Edit Delivery" />
            <GdnForm mode="edit" />
        </div>
    );
}

export function GdnDetailPage() {
    const { id = 'gdn-001' } = useParams();
    const [gdn, setGdn] = useState<GoodsDeliveryNote>();
    const [history, setHistory] = useState<SalesAuditEntry[]>([]);
    const [invoices, setInvoices] = useState<SalesInvoice[]>([]);
    const [activeTab, setActiveTab] = useState('overview');
    const [tabError, setTabError] = useState('');
    const [tabLoading, setTabLoading] = useState('');
    const loadedTabsRef = useRef(new Set<string>());

    useEffect(() => {
        let mounted = true;
        loadedTabsRef.current.clear();
        setActiveTab('overview');
        setGdn(undefined);
        salesApi.deliveries.get(id).then((response) => {
            if (mounted) setGdn(response.data);
        });
        return () => {
            mounted = false;
        };
    }, [id]);

    useEffect(() => {
        if (!gdn || !['invoices', 'history'].includes(activeTab) || loadedTabsRef.current.has(activeTab)) {
            return;
        }

        const currentGdn = gdn;
        let mounted = true;
        setTabLoading(activeTab);
        setTabError('');

        async function loadTab(): Promise<void> {
            try {
                if (activeTab === 'invoices') {
                    const response = await salesApi.invoices.list({ perPage: 20, search: currentGdn.gdnNumber });
                    if (mounted) setInvoices(response.data);
                } else {
                    const response = await salesApi.deliveries.history(id).catch(() => ({ data: [] }));
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
    }, [activeTab, gdn, id]);

    if (!gdn) {
        return <EmptyState description="Loading delivery details..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to={`/sales/deliveries/${gdn.id}/edit`}><Button variant="secondary">Edit</Button></Link>} eyebrow="Delivery / GDN" subtitle="Delivery detail with delivered lines, source SO, inventory effect, invoices, and audit." title={gdn.gdnNumber} />
            <Tabs active={activeTab} items={[{ label: 'Overview', value: 'overview' }, { label: 'Delivered Lines', value: 'lines' }, { label: 'Source Order', value: 'source' }, { label: 'Inventory Effect', value: 'inventory' }, { label: 'Invoices', value: 'invoices' }, { label: 'Documents', value: 'documents' }, { label: 'History / Audit', value: 'history' }]} onChange={setActiveTab} />
            {activeTab === 'overview' ? <SalesWorkflowActions entityId={gdn.id} entityType="gdn_header" status={gdn.status} /> : null}
            {activeTab === 'lines' ? <GdnLineTable rows={gdn.lines} /> : null}
            {activeTab === 'source' ? <SalesSourceReferencePanel reference={gdn.sourceOrder} /> : null}
            {activeTab === 'inventory' ? <EmptyState description="Inventory effect is posted through the backend workflow action for this delivery." title="Inventory effect source-scoped" /> : null}
            {activeTab === 'invoices' ? tabLoading === 'invoices' ? <EmptyState description="Loading related invoices..." title="Loading invoices" /> : tabError ? <EmptyState description={tabError} title="Invoices unavailable" /> : <SalesInvoiceTable rows={invoices.filter((invoice) => invoice.sourceReference === gdn.gdnNumber || invoice.sourceReference === gdn.id)} /> : null}
            {activeTab === 'documents' ? <SalesSourceReferencePanel reference={gdn.gdnNumber} /> : null}
            {activeTab === 'history' ? tabLoading === 'history' ? <EmptyState description="Loading audit timeline..." title="Loading audit" /> : tabError ? <EmptyState description={tabError} title="Audit unavailable" /> : <SalesActivityTimeline rows={history} /> : null}
        </div>
    );
}
