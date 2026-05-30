import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
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
import { customerRefunds, financePostingPreview, inventoryEffects, salesActivity } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { SalesReturn } from '../types/sales.types';

export function SalesReturnListPage() {
    const [rows, setRows] = useState<SalesReturn[]>([]);

    useEffect(() => {
        salesApi.returns.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/returns/new"><Button>Create Return</Button></Link>} eyebrow="Sales" subtitle="Sales returns with backend-owned returnable quantity, stock/AR effects, and refund eligibility." title="Sales Returns" />
            <SearchFilterBar placeholder="Search return number, customer, source document..." />
            {rows.length ? <SalesReturnTable rows={rows} /> : <EmptyState description="No sales returns returned yet." title="No returns" />}
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
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.returns.get(id).then((response) => setRecord(response.data));
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
            {activeTab === 'inventory' ? <GdnInventoryEffectPanel effects={inventoryEffects} /> : null}
            {activeTab === 'finance' ? <SalesFinancePostingPanel preview={financePostingPreview} /> : null}
            {activeTab === 'refunds' ? <CustomerRefundPanel refunds={customerRefunds} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={salesActivity} /> : null}
        </div>
    );
}
