import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
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
import { inventoryEffects, salesActivity, salesInvoices } from '../mock/salesMock';
import { salesApi } from '../services/salesApi';
import type { GoodsDeliveryNote } from '../types/sales.types';

export function GdnListPage() {
    const [rows, setRows] = useState<GoodsDeliveryNote[]>([]);

    useEffect(() => {
        salesApi.deliveries.list().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/sales/deliveries/new"><Button>Create GDN</Button></Link>} eyebrow="Sales" subtitle="Deliveries manage issued quantities and backend-owned stock effects." title="Deliveries / GDN" />
            <SearchFilterBar placeholder="Search GDN number, customer, SO, item..." />
            {rows.length ? <GdnTable rows={rows} /> : <EmptyState description="No deliveries returned yet." title="No deliveries" />}
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
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        salesApi.deliveries.get(id).then((response) => setGdn(response.data));
    }, [id]);

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
            {activeTab === 'inventory' ? <GdnInventoryEffectPanel effects={inventoryEffects.filter((effect) => effect.sourceReference === gdn.gdnNumber)} /> : null}
            {activeTab === 'invoices' ? <SalesInvoiceTable rows={salesInvoices.filter((invoice) => invoice.sourceReference === gdn.gdnNumber)} /> : null}
            {activeTab === 'documents' ? <SalesSourceReferencePanel reference={gdn.gdnNumber} /> : null}
            {activeTab === 'history' ? <SalesActivityTimeline rows={salesActivity} /> : null}
        </div>
    );
}
